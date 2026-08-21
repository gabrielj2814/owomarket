<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Src\Customer\Infrastructure\Eloquent\Models\Customer as EloquentCustomer;
use Src\Order\Infrastructure\Eloquent\Models\Order as EloquentOrder;
use Src\Tenant\Infrastructure\Eloquent\Models\Tenant as ModelsTenant;
use Stancl\Tenancy\Bootstrappers\DatabaseTenancyBootstrapper;
use Stancl\Tenancy\Events\TenantCreated;
use Stancl\Tenancy\Events\TenantDeleted;

beforeEach(function () {
    Event::fake([TenantCreated::class, TenantDeleted::class]);

    config([
        'tenancy.bootstrappers' => array_values(array_filter(
            config('tenancy.bootstrappers', []),
            fn ($bootstrapper) => $bootstrapper !== DatabaseTenancyBootstrapper::class
        )),
    ]);

    if (! Schema::hasTable('customers')) {
        (require base_path('database/migrations/tenant/2025_10_28_144201_create_customers.php'))->up();
    }
    if (! Schema::hasTable('addresses')) {
        (require base_path('database/migrations/tenant/2025_10_28_144231_create_addresses.php'))->up();
    }
    if (! Schema::hasTable('categories')) {
        (require base_path('database/migrations/tenant/2025_10_28_142911_create_categories.php'))->up();
    }
    if (! Schema::hasTable('brands')) {
        (require base_path('database/migrations/tenant/2025_10_28_143000_create_brands.php'))->up();
    }
    if (! Schema::hasTable('products')) {
        (require base_path('database/migrations/tenant/2025_10_28_143038_create_products.php'))->up();
    }
    if (! Schema::hasTable('product_variants')) {
        (require base_path('database/migrations/tenant/2025_10_28_143954_create_product_variants.php'))->up();
    }
    if (! Schema::hasTable('orders')) {
        (require base_path('database/migrations/tenant/2025_10_28_144320_create_orders.php'))->up();
    }
    if (! Schema::hasTable('order_items')) {
        (require base_path('database/migrations/tenant/2025_10_28_144403_create_order_items.php'))->up();
    }
    if (! Schema::hasTable('shipments')) {
        (require base_path('database/migrations/tenant/2025_10_28_144441_create_shipments.php'))->up();
    }

    $tenantId = 't_'.bin2hex(random_bytes(4));
    $this->tenant = ModelsTenant::create([
        'id' => $tenantId,
        'name' => 'Shipment E2E Store',
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

    // Fase 0.3-E: /api-tenant/* dejó de estar abierto (hallazgo A5). Las rutas
    // de backoffice exigen ahora sesión de usuario de la tienda; se autentica
    // aquí para todo el archivo.
    $this->tenantUser = \Src\Tenant\Infrastructure\Eloquent\Models\User::create([
        'id' => (string) \Illuminate\Support\Str::uuid(),
        'name' => 'Store Staff',
        'email' => 'staff_'.bin2hex(random_bytes(5)).'@example.com',
        'password' => bcrypt('Password123!'),
        'type' => 'tenant_owner',
    ]);
    $this->actingAs($this->tenantUser);
});

afterEach(function () {
    if (tenancy()->initialized) {
        tenancy()->end();
    }
});

it('executes full shipment lifecycle end-to-end with order synchronization and metrics', function () {
    // 1. Initial metrics: 0 shipments
    $initialMetrics = $this->getJson("http://{$this->domain}/api-tenant/shipment/metrics");
    $initialMetrics->assertStatus(200)
        ->assertJsonPath('data.total_shipments', 0)
        ->assertJsonPath('data.total_shipping_cost', 0);

    // 2. Setup Customer & Order in 'processing' status
    $customer = EloquentCustomer::create([
        'id' => (string) Str::uuid(),
        'name' => 'Esteban Paredes',
        'email' => 'esteban@goleador.cl',
        'is_active' => true,
    ]);

    $order1 = EloquentOrder::create([
        'id' => (string) Str::uuid(),
        'order_number' => 'ORD-E2E-SHIP-01',
        'customer_id' => $customer->id,
        'status' => 'processing',
        'payment_status' => 'paid',
        'payment_method' => 'webpay',
        'currency' => 'USD',
        'subtotal' => 150.00,
        'shipping_amount' => 15.00,
        'tax_amount' => 28.50,
        'total' => 193.50,
    ]);

    // 3. Create Shipment in 'pending' status without tracking number
    $createShipmentRes = $this->postJson("http://{$this->domain}/api-tenant/shipment/create", [
        'order_id' => $order1->id,
        'carrier' => 'Chilexpress',
        'service' => 'Prioritario 24h',
        'cost' => 15.00,
        'notes' => 'Caja frágil, manipular con cuidado',
        'estimated_delivery' => '2026-08-20',
    ]);

    $createShipmentRes->assertStatus(201)
        ->assertJsonPath('status', 'success')
        ->assertJsonPath('data.order_id', $order1->id)
        ->assertJsonPath('data.carrier', 'Chilexpress')
        ->assertJsonPath('data.service', 'Prioritario 24h')
        ->assertJsonPath('data.cost', 15)
        ->assertJsonPath('data.status', 'pending');

    $shipmentId = $createShipmentRes->json('data.id');

    // Verify order remains in 'processing' when shipment is pending
    $order1->refresh();
    expect($order1->status)->toBe('processing');

    // 4. Verify intermediate metrics (1 total, 1 pending)
    $metricsAfterCreate = $this->getJson("http://{$this->domain}/api-tenant/shipment/metrics");
    $metricsAfterCreate->assertStatus(200)
        ->assertJsonPath('data.total_shipments', 1)
        ->assertJsonPath('data.pending_shipments', 1)
        ->assertJsonPath('data.in_transit_shipments', 0)
        ->assertJsonPath('data.delivered_shipments', 0);

    // 5. Update Tracking: Assign courier tracking number -> Status changes to in_transit
    $trackRes = $this->postJson("http://{$this->domain}/api-tenant/shipment/{$shipmentId}/tracking", [
        'tracking_number' => 'CHI-7788990011',
        'carrier' => 'Chilexpress Express',
        'service' => 'Prioritario AM',
        'estimated_delivery' => '2026-08-20',
        'notes' => 'Despachado en sucursal central',
    ]);

    $trackRes->assertStatus(200)
        ->assertJsonPath('data.tracking_number', 'CHI-7788990011')
        ->assertJsonPath('data.carrier', 'Chilexpress Express')
        ->assertJsonPath('data.status', 'in_transit');

    // Verify Order is automatically synced to 'shipped'
    $order1->refresh();
    expect($order1->status)->toBe('shipped')
        ->and($order1->shipping_method)->toBe('Chilexpress Express');

    // 6. Consult shipment by ID and by Order ID
    $getByIdRes = $this->getJson("http://{$this->domain}/api-tenant/shipment/{$shipmentId}");
    $getByIdRes->assertStatus(200)
        ->assertJsonPath('data.id', $shipmentId)
        ->assertJsonPath('data.tracking_number', 'CHI-7788990011');

    $getByOrderRes = $this->getJson("http://{$this->domain}/api-tenant/shipment/order/{$order1->id}");
    $getByOrderRes->assertStatus(200)
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $shipmentId);

    // 7. Filter shipments by status and carrier
    $filterRes = $this->postJson("http://{$this->domain}/api-tenant/shipment/filter", [
        'status' => 'in_transit',
        'carrier' => 'Chilexpress',
    ]);
    $filterRes->assertStatus(200)
        ->assertJsonCount(1, 'data.data');

    // 8. Mark shipment as delivered -> Status changes to delivered
    $deliverRes = $this->postJson("http://{$this->domain}/api-tenant/shipment/{$shipmentId}/deliver");
    $deliverRes->assertStatus(200)
        ->assertJsonPath('data.status', 'delivered')
        ->assertJsonPath('data.delivered_at', fn ($dt) => ! empty($dt));

    // Verify Order is automatically synced to 'delivered'
    $order1->refresh();
    expect($order1->status)->toBe('delivered')
        ->and($order1->delivered_at)->not->toBeNull();

    // 9. Create a second order and shipment directly with tracking number
    $order2 = EloquentOrder::create([
        'id' => (string) Str::uuid(),
        'order_number' => 'ORD-E2E-SHIP-02',
        'customer_id' => $customer->id,
        'status' => 'processing',
        'payment_status' => 'paid',
        'payment_method' => 'cash',
        'currency' => 'USD',
        'subtotal' => 80.00,
        'total' => 80.00,
    ]);

    $createShipment2Res = $this->postJson("http://{$this->domain}/api-tenant/shipment/create", [
        'order_id' => $order2->id,
        'carrier' => 'DHL',
        'service' => 'Next Day Flight',
        'cost' => 25.00,
        'tracking_number' => 'DHL-55443322',
    ]);
    $createShipment2Res->assertStatus(201)
        ->assertJsonPath('data.status', 'in_transit');

    $order2->refresh();
    expect($order2->status)->toBe('shipped');

    // 10. Verify Final Aggregated Metrics
    $finalMetrics = $this->getJson("http://{$this->domain}/api-tenant/shipment/metrics");
    $finalMetrics->assertStatus(200)
        ->assertJsonPath('data.total_shipments', 2)
        ->assertJsonPath('data.pending_shipments', 0)
        ->assertJsonPath('data.in_transit_shipments', 1)
        ->assertJsonPath('data.delivered_shipments', 1)
        ->assertJsonPath('data.total_shipping_cost', 40);
});
