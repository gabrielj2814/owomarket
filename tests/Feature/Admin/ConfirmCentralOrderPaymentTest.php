<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Src\CentralMarketplace\Infrastructure\Eloquent\Models\CentralOrderDispatch;
use Src\Customer\Infrastructure\Eloquent\Models\Customer as EloquentCustomer;
use Src\Monetization\Application\UseCases\GenerateTenantCommissionSettlementUseCase;
use Src\Monetization\Infrastructure\Eloquent\Models\PlatformCommission;
use Src\Order\Infrastructure\Eloquent\Models\CentralOrder;
use Src\Order\Infrastructure\Eloquent\Models\Order as EloquentOrder;
use Src\Tenant\Infrastructure\Eloquent\Models\Tenant;
use Src\Tenant\Infrastructure\Eloquent\Models\User;
use Stancl\Tenancy\Bootstrappers\DatabaseTenancyBootstrapper;
use Stancl\Tenancy\Events\TenantCreated;
use Stancl\Tenancy\Events\TenantDeleted;

/**
 * Hallazgo A: confirmar el cobro de un pedido central no existía. La columna
 * `central_orders.payment_status` se lee en seis sitios y no la escribía nadie: nacía
 * `pending` y sólo la resolución de disputas la movía, a `refunded` o `cancelled`.
 *
 * Hallazgo C: y esa resolución de disputas anulaba comisiones con el id del pedido central,
 * cuando las comisiones guardan el de la tienda. No casaba con ninguna fila.
 */
beforeEach(function () {
    Event::fake([TenantCreated::class, TenantDeleted::class]);

    config([
        'tenancy.bootstrappers' => array_values(array_filter(
            config('tenancy.bootstrappers', []),
            fn ($b) => $b !== DatabaseTenancyBootstrapper::class
        )),
    ]);

    // `EloquentOrderRepository::save()` reescribe las lineas del pedido, y eso arrastra
    // `product_variants`: sin esas tablas el guardado revienta con un `no such table`
    // disfrazado de error 400.
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
        'name' => 'Tienda del Cobro Central',
        'slug' => 'cobro-'.Str::random(4),
        'status' => 'active',
        'request' => 'approved',
    ]);

    $this->tenant->domains()->create([
        'id' => (string) Str::uuid(),
        'domain' => "{$this->tenant->slug}.owomarket.local",
    ]);
});

/**
 * Un pedido central pagado por el comprador a la PLATAFORMA, ya despachado a una tienda:
 * pedido central `pending`, pedido de tienda con el pago `pending`, y la comisión en
 * `awaiting_payment` — que es como nace desde N15.
 *
 * @return array{0: CentralOrder, 1: EloquentOrder, 2: PlatformCommission}
 */
function pedidoCentralDespachado(Tenant $tenant, string $paymentStatus = 'pending'): array
{
    $centralOrder = CentralOrder::create([
        'id' => (string) Str::uuid(),
        'order_number' => 'ORD-'.strtoupper(Str::random(8)),
        'customer_name' => 'Ana Compradora',
        'customer_email' => 'ana@example.com',
        'subtotal' => 100.00,
        'total' => 100.00,
        'payment_method' => 'pago_movil',
        'payment_status' => $paymentStatus,
        'payment_details' => ['reference_number' => 'PM-9928172'],
        'status' => 'processing',
    ]);

    $customer = EloquentCustomer::create([
        'id' => (string) Str::uuid(),
        'name' => 'Ana Compradora',
        'email' => 'ana-'.Str::random(6).'@example.com',
        'is_active' => true,
    ]);

    $tenantOrder = EloquentOrder::create([
        'id' => (string) Str::uuid(),
        'order_number' => 'T-'.strtoupper(Str::random(6)),
        'customer_id' => $customer->id,
        'status' => 'confirmed',
        'payment_status' => 'pending',
        'payment_method' => 'pago_movil',
        'currency' => 'USD',
        'subtotal' => 100.00,
        'total' => 100.00,
    ]);

    CentralOrderDispatch::create([
        'id' => (string) Str::uuid(),
        'central_order_id' => $centralOrder->id,
        'tenant_id' => $tenant->id,
        'tenant_order_id' => $tenantOrder->id,
        'status' => 'dispatched',
        'attempts' => 1,
        'dispatched_at' => now(),
    ]);

    DB::table('payments')->insert([
        'id' => (string) Str::uuid(),
        'order_id' => $tenantOrder->id,
        'payment_gateway' => 'pago_movil',
        'transaction_id' => 'PM-9928172',
        'amount' => 100.00,
        'fee' => 0.0,
        'status' => 'pending',
        'currency' => 'USD',
        'paid_at' => null,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $commission = PlatformCommission::create([
        'id' => (string) Str::uuid(),
        'tenant_id' => $tenant->id,
        'order_id' => $tenantOrder->id,
        'central_order_id' => $centralOrder->id,
        'order_number' => $tenantOrder->order_number,
        'order_total' => 100.00,
        'commission_rate' => 8.00,
        'commission_amount' => 8.00,
        'currency' => 'USD',
        'status' => 'awaiting_payment',
        'payment_gateway' => 'pago_movil',
    ]);

    return [$centralOrder, $tenantOrder, $commission];
}

it('confirmar el cobro central pone al día el pedido de la tienda, su pago y su comisión (A)', function () {
    [$centralOrder, $tenantOrder, $commission] = pedidoCentralDespachado($this->tenant);

    $this->actingAs($this->adminUser)
        ->postJson("/admin/api/orders/{$centralOrder->id}/confirm-payment", [
            'reference' => 'PM-9928172',
            'notes' => 'Cotejado contra el extracto del banco.',
        ])
        ->assertStatus(200);

    $centralOrder->refresh();
    expect($centralOrder->payment_status)->toBe('paid')
        ->and($centralOrder->metadata['payment_confirmation']['reference'])->toBe('PM-9928172')
        ->and($centralOrder->metadata['payment_confirmation']['confirmed_by'])->toBe($this->adminUser->id);

    // El pedido de la tienda, por la entidad y con las guardas de OR1.
    $tenantOrder->refresh();
    expect($tenantOrder->payment_status)->toBe('paid');

    // La fila de `payments` dejaba de moverse de `pending` para siempre.
    expect(DB::table('payments')->where('order_id', $tenantOrder->id)->value('status'))->toBe('completed');

    $commission->refresh();
    expect($commission->status)->toBe('pending');
});

it('y con eso la tienda ya puede cobrar su venta central (A)', function () {
    // Esto es lo que hace 🔴 al hallazgo y no un problema de métricas:
    // `GenerateTenantCommissionSettlementUseCase` lee comisiones en `pending` tanto para
    // `collection` como para `payout`. Sin confirmar el cobro, el `payout` --el dinero que
    // la plataforma le debe al comerciante-- no se podía generar nunca.
    [$centralOrder] = pedidoCentralDespachado($this->tenant);

    $generar = app(GenerateTenantCommissionSettlementUseCase::class);

    expect(fn () => $generar->execute($this->tenant->id, 'payout'))
        ->toThrow(Exception::class);

    $this->actingAs($this->adminUser)
        ->postJson("/admin/api/orders/{$centralOrder->id}/confirm-payment", [])
        ->assertStatus(200);

    $liquidacion = $generar->execute($this->tenant->id, 'payout');

    expect($liquidacion->type)->toBe('payout')
        ->and((float) $liquidacion->gross_sales_amount)->toBe(100.00)
        ->and((float) $liquidacion->commission_amount)->toBe(8.00)
        ->and((float) $liquidacion->net_amount)->toBe(92.00);
});

it('confirmar antes del despacho no necesita tocar ninguna tienda (A)', function () {
    // Si el pago se confirma antes de que corra `DispatchCentralOrderJob` no hay pedidos de
    // tienda que poner al día. No hace falta código para ese caso: el propio despacho lee
    // `paid: ($centralOrder->payment_status ?? 'pending') === 'paid'` y crea la comisión ya
    // cobrable y la fila de `payments` en `completed`. Los dos órdenes convergen.
    $centralOrder = CentralOrder::create([
        'id' => (string) Str::uuid(),
        'order_number' => 'ORD-'.strtoupper(Str::random(8)),
        'customer_name' => 'Sin Despachar',
        'customer_email' => 'sin@example.com',
        'subtotal' => 50.00,
        'total' => 50.00,
        'payment_method' => 'pago_movil',
        'payment_status' => 'pending',
        'status' => 'pending',
    ]);

    $respuesta = $this->actingAs($this->adminUser)
        ->postJson("/admin/api/orders/{$centralOrder->id}/confirm-payment", []);

    $respuesta->assertStatus(200);
    expect($respuesta->json('data.tenant_orders'))->toBe([])
        ->and($respuesta->json('data.commissions_activated'))->toBe(0);

    expect($centralOrder->refresh()->payment_status)->toBe('paid');
});

it('rechaza confirmar dos veces y rechaza un pedido reembolsado (A)', function () {
    [$yaPagado] = pedidoCentralDespachado($this->tenant, 'paid');

    $this->actingAs($this->adminUser)
        ->postJson("/admin/api/orders/{$yaPagado->id}/confirm-payment", [])
        ->assertStatus(422);

    [$reembolsado] = pedidoCentralDespachado($this->tenant, 'refunded');

    $this->actingAs($this->adminUser)
        ->postJson("/admin/api/orders/{$reembolsado->id}/confirm-payment", [])
        ->assertStatus(422);
});

it('no confirma cobros sin sesión de administrador (A)', function () {
    [$centralOrder] = pedidoCentralDespachado($this->tenant);

    $respuesta = $this->postJson("/admin/api/orders/{$centralOrder->id}/confirm-payment", []);

    expect($respuesta->status())->not->toBe(200);
    expect($centralOrder->refresh()->payment_status)->toBe('pending');
});

it('reembolsar por disputa anula la comisión de verdad (C)', function () {
    // Este test falla antes del arreglo: el `where` usaba el id del pedido central y las
    // comisiones guardan el de la tienda, así que la comisión sobrevivía al reembolso y la
    // plataforma se quedaba el dinero.
    [$centralOrder, , $commission] = pedidoCentralDespachado($this->tenant);

    $this->actingAs($this->adminUser)
        ->postJson("/admin/api/orders/{$centralOrder->id}/resolve-dispute", [
            'resolution_type' => 'refund',
            'reason' => 'Producto no recibido',
        ])
        ->assertStatus(200);

    expect($commission->refresh()->status)->toBe('refunded');
});
