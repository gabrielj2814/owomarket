<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Src\Customer\Infrastructure\Eloquent\Models\Customer as EloquentCustomer;
use Src\Monetization\Infrastructure\Eloquent\Models\PlatformCommission;
use Src\Order\Infrastructure\Eloquent\Models\Order as EloquentOrder;
use Src\Tenant\Infrastructure\Eloquent\Models\Tenant as ModelsTenant;
use Stancl\Tenancy\Bootstrappers\DatabaseTenancyBootstrapper;
use Stancl\Tenancy\Events\TenantCreated;
use Stancl\Tenancy\Events\TenantDeleted;

/**
 * Fase 3b: lo que le queda al comerciante cuando deja de poder declarar un cobro.
 *
 * El comprador a veces le pasa la referencia por WhatsApp en vez de por el checkout. El
 * comerciante lo sabe; la plataforma sólo ve un depósito sin dueño en su extracto. Reportarla
 * es la forma de que esa información llegue a quien tiene que cuadrarla.
 *
 * **Es una pista, no un hecho.** Confirmar el cobro sigue siendo de la plataforma.
 */
beforeEach(function () {
    Event::fake([TenantCreated::class, TenantDeleted::class]);

    config([
        'tenancy.bootstrappers' => array_values(array_filter(
            config('tenancy.bootstrappers', []),
            fn ($b) => $b !== DatabaseTenancyBootstrapper::class
        )),
    ]);

    foreach ([
        'categories' => '2025_10_28_142911_create_categories',
        'brands' => '2025_10_28_143000_create_brands',
        'products' => '2025_10_28_143038_create_products',
        'product_variants' => '2025_10_28_143954_create_product_variants',
        'customers' => '2025_10_28_144201_create_customers',
        'orders' => '2025_10_28_144320_create_orders',
        'order_items' => '2025_10_28_144403_create_order_items',
    ] as $table => $migration) {
        if (! Schema::hasTable($table)) {
            (require base_path("database/migrations/tenant/{$migration}.php"))->up();
        }
    }

    $tenantId = 't_rep_'.bin2hex(random_bytes(3));
    $this->tenant = ModelsTenant::create([
        'id' => $tenantId,
        'name' => 'Tienda Reporte',
        'slug' => $tenantId,
        'status' => 'active',
        'request' => 'approved',
    ]);
    $this->domain = "{$tenantId}.localhost";
    $this->tenant->domains()->create([
        'id' => (string) Str::uuid(),
        'domain' => $this->domain,
    ]);

    tenancy()->initialize($this->tenant);
    $this->tenantUser = actingAsTenantOwner();

    $customer = EloquentCustomer::create([
        'id' => (string) Str::uuid(),
        'name' => 'Comprador',
        'email' => 'c-'.Str::random(6).'@example.com',
        'is_active' => true,
    ]);

    $this->order = EloquentOrder::create([
        'id' => (string) Str::uuid(),
        'order_number' => 'ORD-'.strtoupper(Str::random(6)),
        'customer_id' => $customer->id,
        'status' => 'confirmed',
        'payment_status' => 'pending',
        'payment_method' => 'pago_movil',
        'currency' => 'USD',
        'subtotal' => 100.00,
        'total' => 100.00,
    ]);
    tenancy()->end();

    $this->comision = PlatformCommission::create([
        'id' => (string) Str::uuid(),
        'tenant_id' => $this->tenant->id,
        'order_id' => $this->order->id,
        'order_number' => $this->order->order_number,
        'order_total' => 100.00,
        'commission_rate' => 8.00,
        'commission_amount' => 8.00,
        'currency' => 'USD',
        'exchange_rate' => 50.00,
        'status' => 'awaiting_payment',
        'payment_gateway' => 'pago_movil',
        'metadata' => ['source' => 'storefront_checkout', 'payment_reference' => 'PM-DEL-CHECKOUT'],
    ]);

    tenancy()->initialize($this->tenant);
});

afterEach(function () {
    if (tenancy()->initialized) {
        tenancy()->end();
    }
});

it('el comerciante reporta una referencia y llega a la comisión, que es donde el admin la lee', function () {
    $this->postJson("http://{$this->domain}/api-tenant/order/{$this->order->id}/report-payment", [
        'reference' => 'PM-POR-WHATSAPP',
        'notes' => 'El cliente me la pasó por mensaje.',
    ])->assertStatus(200);

    $metadata = $this->comision->refresh()->metadata;

    expect($metadata['reported_reference']['reference'])->toBe('PM-POR-WHATSAPP')
        // Aparte de la que puso el comprador en el checkout, no encima: si no coinciden, eso
        // es justo lo que el administrador necesita ver.
        ->and($metadata['payment_reference'])->toBe('PM-DEL-CHECKOUT');
});

it('reportar no confirma nada: el cobro sigue pendiente', function () {
    // Es una pista, no un hecho. Si reportar cobrara, el comerciante tendría otra vez la llave
    // del dinero por otro camino.
    $this->postJson("http://{$this->domain}/api-tenant/order/{$this->order->id}/report-payment", [
        'reference' => 'PM-POR-WHATSAPP',
    ])->assertStatus(200);

    expect($this->comision->refresh()->status)->toBe('awaiting_payment');

    tenancy()->initialize($this->tenant);
    expect(EloquentOrder::find($this->order->id)->payment_status)->toBe('pending');
});

it('exige una referencia no vacía', function () {
    $this->postJson("http://{$this->domain}/api-tenant/order/{$this->order->id}/report-payment", [
        'reference' => '   ',
    ])->assertStatus(422);
});
