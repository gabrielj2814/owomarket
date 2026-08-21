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
        'name' => 'Shipment API Test Store',
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

    $this->customer = EloquentCustomer::create([
        'id' => (string) Str::uuid(),
        'name' => 'Consuelo Matamala',
        'email' => 'consuelo@test.cl',
        'is_active' => true,
    ]);

    $this->order = EloquentOrder::create([
        'id' => (string) Str::uuid(),
        'order_number' => 'ORD-API-SHIP-01',
        'customer_id' => $this->customer->id,
        'status' => 'processing',
        'payment_status' => 'paid',
        'payment_method' => 'webpay',
        'currency' => 'USD',
        'subtotal' => 120.00,
        'total' => 120.00,
    ]);

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

it('GET /api-tenant/shipment/metrics returns initial metrics', function () {
    $response = $this->getJson("http://{$this->domain}/api-tenant/shipment/metrics");

    $response->assertStatus(200)
        ->assertJsonPath('status', 'success')
        ->assertJsonPath('data.total_shipments', 0)
        ->assertJsonPath('data.total_shipping_cost', 0);
});

it('POST /api-tenant/shipment/create creates a shipment and returns 201', function () {
    $response = $this->postJson("http://{$this->domain}/api-tenant/shipment/create", [
        'order_id' => $this->order->id,
        'carrier' => 'Chilexpress',
        'service' => 'Express 24h',
        'cost' => 14.50,
        'notes' => 'Entregar antes de las 18:00',
    ]);

    $response->assertStatus(201)
        ->assertJsonPath('status', 'success')
        ->assertJsonPath('data.order_id', $this->order->id)
        ->assertJsonPath('data.carrier', 'Chilexpress')
        ->assertJsonPath('data.service', 'Express 24h')
        ->assertJsonPath('data.cost', 14.5)
        ->assertJsonPath('data.status', 'pending');
});

it('POST /api-tenant/shipment/create returns 422 on validation failure', function () {
    $response = $this->postJson("http://{$this->domain}/api-tenant/shipment/create", [
        'order_id' => 'non-existent-order-id',
        'carrier' => '',
        'service' => '',
    ]);

    $response->assertStatus(422)
        ->assertJsonPath('status', 'error')
        ->assertJsonStructure(['errors']);
});

it('GET /api-tenant/shipment/{id} and GET /api-tenant/shipment/order/{orderId} retrieve shipments', function () {
    $createRes = $this->postJson("http://{$this->domain}/api-tenant/shipment/create", [
        'order_id' => $this->order->id,
        'carrier' => 'Starken',
        'service' => 'Standard',
        'cost' => 9.99,
    ]);
    $shipmentId = $createRes->json('data.id');

    $getById = $this->getJson("http://{$this->domain}/api-tenant/shipment/{$shipmentId}");
    $getById->assertStatus(200)
        ->assertJsonPath('data.id', $shipmentId)
        ->assertJsonPath('data.carrier', 'Starken');

    $getByOrder = $this->getJson("http://{$this->domain}/api-tenant/shipment/order/{$this->order->id}");
    $getByOrder->assertStatus(200)
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $shipmentId);
});

it('POST /api-tenant/shipment/{id}/tracking updates tracking number and syncs order to shipped', function () {
    $createRes = $this->postJson("http://{$this->domain}/api-tenant/shipment/create", [
        'order_id' => $this->order->id,
        'carrier' => 'Chilexpress',
        'service' => 'Overnight',
        'cost' => 12.00,
    ]);
    $shipmentId = $createRes->json('data.id');

    $trackRes = $this->postJson("http://{$this->domain}/api-tenant/shipment/{$shipmentId}/tracking", [
        'tracking_number' => 'CHX-77665544',
        'carrier' => 'Chilexpress Express',
        'service' => 'Next Morning',
    ]);

    $trackRes->assertStatus(200)
        ->assertJsonPath('data.tracking_number', 'CHX-77665544')
        ->assertJsonPath('data.status', 'in_transit');

    $this->order->refresh();
    expect($this->order->status)->toBe('shipped');
});

it('POST /api-tenant/shipment/{id}/deliver marks shipment as delivered and syncs order to delivered', function () {
    $createRes = $this->postJson("http://{$this->domain}/api-tenant/shipment/create", [
        'order_id' => $this->order->id,
        'carrier' => 'DHL',
        'service' => 'Express',
        'tracking_number' => 'DHL-998811',
    ]);
    $shipmentId = $createRes->json('data.id');

    $deliverRes = $this->postJson("http://{$this->domain}/api-tenant/shipment/{$shipmentId}/deliver");
    $deliverRes->assertStatus(200)
        ->assertJsonPath('data.status', 'delivered');

    $this->order->refresh();
    expect($this->order->status)->toBe('delivered')
        ->and($this->order->delivered_at)->not->toBeNull();
});

it('POST /api-tenant/shipment/filter filters shipments with search and pagination', function () {
    $this->postJson("http://{$this->domain}/api-tenant/shipment/create", [
        'order_id' => $this->order->id,
        'carrier' => 'Blue Express',
        'service' => 'Standard',
        'cost' => 8.50,
    ]);

    $filterRes = $this->postJson("http://{$this->domain}/api-tenant/shipment/filter", [
        'carrier' => 'Blue Express',
        'per_page' => 10,
        'page' => 1,
    ]);

    $filterRes->assertStatus(200)
        ->assertJsonPath('status', 'success')
        ->assertJsonCount(1, 'data.data')
        ->assertJsonPath('data.total', 1)
        ->assertJsonPath('data.data.0.carrier', 'Blue Express');
});
