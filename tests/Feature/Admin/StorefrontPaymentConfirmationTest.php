<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Src\Customer\Infrastructure\Eloquent\Models\Customer as EloquentCustomer;
use Src\Monetization\Infrastructure\Eloquent\Models\PlatformCommission;
use Src\Order\Infrastructure\Eloquent\Models\Order as EloquentOrder;
use Src\Tenant\Infrastructure\Eloquent\Models\Tenant;
use Src\Tenant\Infrastructure\Eloquent\Models\User;
use Stancl\Tenancy\Bootstrappers\DatabaseTenancyBootstrapper;
use Stancl\Tenancy\Events\TenantCreated;
use Stancl\Tenancy\Events\TenantDeleted;

/**
 * Fase 3 de `planes/por_hacer/PLAN_COBRO_UNIFICADO.md`.
 *
 * Desde que la plataforma cobra todas las ventas, el comerciante ya no puede decir si el dinero
 * llegó: no tiene acceso a ese extracto bancario. El que cobra es el que confirma.
 *
 * La lista sale de `platform_commissions` en `awaiting_payment` porque es la única proyección
 * central que ya existe de una venta de tienda: los pedidos del escaparate viven en la base de
 * cada inquilino y ninguna consulta central los ve.
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
        'payments' => '2025_10_28_144517_create_payments',
    ] as $table => $migration) {
        if (! Schema::hasTable($table)) {
            (require base_path("database/migrations/tenant/{$migration}.php"))->up();
        }
    }

    $this->adminUser = User::create([
        'id' => (string) Str::uuid(),
        'name' => 'Super Admin',
        'email' => 'admin_'.Str::random(6).'@owomarket.com',
        'password' => bcrypt('password123'),
        'type' => 'super_admin',
        'is_active' => true,
    ]);

    $this->tenant = Tenant::create([
        'id' => 'shop-'.Str::random(6),
        'name' => 'Tienda del Escaparate',
        'slug' => 'escaparate-'.Str::random(4),
        'status' => 'active',
        'request' => 'approved',
    ]);
});

/** Una venta del escaparate: pedido de tienda, su pago pendiente y su comisión sin cobrar. */
function ventaDeEscaparate(Tenant $tenant, string $referencia = 'PM-77665544'): PlatformCommission
{
    $customer = EloquentCustomer::create([
        'id' => (string) Str::uuid(),
        'name' => 'Comprador',
        'email' => 'c-'.Str::random(6).'@example.com',
        'is_active' => true,
    ]);

    $order = EloquentOrder::create([
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

    DB::table('payments')->insert([
        'id' => (string) Str::uuid(),
        'order_id' => $order->id,
        'payment_gateway' => 'pago_movil',
        'transaction_id' => $referencia,
        'amount' => 100.00,
        'fee' => 0.0,
        'status' => 'pending',
        'currency' => 'USD',
        'paid_at' => null,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    return PlatformCommission::create([
        'id' => (string) Str::uuid(),
        'tenant_id' => $tenant->id,
        'order_id' => $order->id,
        // Sin `central_order_id`: es una venta del escaparate, no del marketplace central.
        'order_number' => $order->order_number,
        'order_total' => 100.00,
        'commission_rate' => 8.00,
        'commission_amount' => 8.00,
        'currency' => 'USD',
        'exchange_rate' => 50.00,
        'status' => 'awaiting_payment',
        'payment_gateway' => 'pago_movil',
        'metadata' => ['source' => 'storefront_checkout', 'payment_reference' => $referencia],
    ]);
}

it('lista los cobros pendientes de todas las tiendas, con la referencia del comprador', function () {
    ventaDeEscaparate($this->tenant, 'PM-11112222');

    $respuesta = $this->actingAs($this->adminUser)->getJson('/admin/api/storefront-payments');

    $respuesta->assertStatus(200)
        ->assertJsonPath('data.payments.total', 1)
        ->assertJsonPath('data.payments.data.0.tenant_name', 'Tienda del Escaparate')
        // Es la que el administrador busca en el extracto del banco.
        ->assertJsonPath('data.payments.data.0.payment_reference', 'PM-11112222')
        // Y en bolívares, que es lo que el comprador pagó de verdad: 100 USD x 50.
        ->assertJsonPath('data.payments.data.0.total_ves', 5000)
        ->assertJsonPath('data.metrics.pending_count', 1);
});

it('confirmar el cobro pone al día el pedido, su pago y su comisión', function () {
    $comision = ventaDeEscaparate($this->tenant);

    $this->actingAs($this->adminUser)
        ->postJson("/admin/api/storefront-payments/{$comision->id}/confirm", [
            'reference' => 'PM-77665544',
            'notes' => 'Cotejado contra el extracto.',
        ])
        ->assertStatus(200);

    $comision->refresh();
    expect($comision->status)->toBe('pending')
        ->and($comision->metadata['payment_confirmation']['confirmed_by'])->toBe($this->adminUser->id)
        // La referencia del comprador se conserva junto a la del administrador, no debajo:
        // si no coinciden, eso es justo lo que hay que poder ver después.
        ->and($comision->metadata['payment_reference'])->toBe('PM-77665544');

    expect(EloquentOrder::find($comision->order_id)->payment_status)->toBe('paid');
    expect(DB::table('payments')->where('order_id', $comision->order_id)->value('status'))->toBe('completed');
});

it('un cobro confirmado desaparece de la lista de pendientes', function () {
    $comision = ventaDeEscaparate($this->tenant);

    $this->actingAs($this->adminUser)
        ->postJson("/admin/api/storefront-payments/{$comision->id}/confirm", [])
        ->assertStatus(200);

    $this->actingAs($this->adminUser)
        ->getJson('/admin/api/storefront-payments')
        ->assertJsonPath('data.payments.total', 0);
});

it('rechaza confirmar dos veces el mismo cobro', function () {
    $comision = ventaDeEscaparate($this->tenant);

    $this->actingAs($this->adminUser)
        ->postJson("/admin/api/storefront-payments/{$comision->id}/confirm", [])
        ->assertStatus(200);

    // Confirmar dos veces mentiría sobre cuándo entró el dinero, y esa fecha es la que
    // sostiene cualquier reclamación posterior.
    $this->actingAs($this->adminUser)
        ->postJson("/admin/api/storefront-payments/{$comision->id}/confirm", [])
        ->assertStatus(422);
});

it('no confirma cobros sin sesión de administrador', function () {
    $comision = ventaDeEscaparate($this->tenant);

    $respuesta = $this->postJson("/admin/api/storefront-payments/{$comision->id}/confirm", []);

    expect($respuesta->status())->not->toBe(200);
    expect($comision->refresh()->status)->toBe('awaiting_payment');
});
