<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Src\Customer\Domain\Entities\Customer;
use Src\Customer\Infrastructure\Eloquent\Repositories\EloquentCustomerRepository;
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

    $tenantId = 't_'.bin2hex(random_bytes(4));
    $this->tenant = ModelsTenant::create([
        'id' => $tenantId,
        'name' => 'Order API Store',
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

    $this->customer = Customer::create(
        name: 'Matías Fernandez',
        email: 'matias@test.cl',
        phone: '+56911223344'
    );
    (new EloquentCustomerRepository)->save($this->customer);

    $this->product = \Src\Product\Infrastructure\Eloquent\Models\Product::create([
        'id' => (string) Str::uuid(),
        'name' => 'Audífonos Bluetooth Pro',
        'slug' => 'audifonos-bluetooth-pro',
        'sku' => 'AUD-BT-PRO',
        'price' => 49.90,
        'quantity' => 20,
        'is_visible' => true,
    ]);

    // Fase 0.3-E: /api-tenant/* dejó de estar abierto (hallazgo A5). Las rutas
    // de backoffice exigen ahora sesión de usuario de la tienda; se autentica
    // aquí para todo el archivo.
    $this->tenantUser = actingAsTenantOwner();
});

afterEach(function () {
    if (tenancy()->initialized) {
        tenancy()->end();
    }
});

it('POST /api-tenant/order/create creates order with items and returns 201', function () {
    $payload = [
        'customer_id' => $this->customer->id()->value(),
        'payment_method' => 'credit_card',
        'currency' => 'USD',
        'tax_amount' => 9.48,
        'shipping_amount' => 5.00,
        'discount_amount' => 4.38,
        'notes' => 'Empacar para regalo',
        'items' => [
            [
                'product_id' => $this->product->id,
                'product_name' => $this->product->name,
                'sku' => $this->product->sku,
                'price' => 49.90,
                'quantity' => 1,
            ],
        ],
    ];

    $response = $this->postJson("http://{$this->domain}/api-tenant/order/create", $payload);

    $response->assertStatus(201)
        ->assertJsonPath('status', 'success')
        ->assertJsonPath('data.customer_id', $this->customer->id()->value())
        ->assertJsonPath('data.subtotal', fn ($subtotal) => abs($subtotal - 49.90) < 0.01)
        ->assertJsonPath('data.total', fn ($total) => abs($total - 60.00) < 0.01)
        ->assertJsonCount(1, 'data.items');
});

it('POST /api-tenant/order/create returns 422 on validation failure', function () {
    $response = $this->postJson("http://{$this->domain}/api-tenant/order/create", [
        'customer_id' => 'non-existent-uuid',
        'payment_method' => '',
        'items' => [],
    ]);

    $response->assertStatus(422)
        ->assertJsonPath('status', 'error')
        ->assertJsonStructure(['errors' => ['customer_id', 'payment_method', 'items']]);
});

it('GET /api-tenant/order/{id} and /number/{orderNumber} return existing order', function () {
    $createRes = $this->postJson("http://{$this->domain}/api-tenant/order/create", [
        'customer_id' => $this->customer->id()->value(),
        'payment_method' => 'webpay',
        'items' => [
            [
                'product_id' => $this->product->id,
                'product_name' => $this->product->name,
                'sku' => $this->product->sku,
                'price' => 49.90,
                'quantity' => 2,
            ],
        ],
    ]);

    $orderId = $createRes->json('data.id');
    $orderNumber = $createRes->json('data.order_number');

    // Consult by ID
    $getById = $this->getJson("http://{$this->domain}/api-tenant/order/{$orderId}");
    $getById->assertStatus(200)
        ->assertJsonPath('data.id', $orderId)
        ->assertJsonPath('data.order_number', $orderNumber);

    // Consult by Order Number
    $getByNum = $this->getJson("http://{$this->domain}/api-tenant/order/number/{$orderNumber}");
    $getByNum->assertStatus(200)
        ->assertJsonPath('data.id', $orderId);
});

it('POST /api-tenant/order/filter returns paginated orders', function () {
    $this->postJson("http://{$this->domain}/api-tenant/order/create", [
        'customer_id' => $this->customer->id()->value(),
        'payment_method' => 'stripe',
        'items' => [
            [
                'product_id' => $this->product->id,
                'product_name' => $this->product->name,
                'sku' => $this->product->sku,
                'price' => 49.90,
                'quantity' => 1,
            ],
        ],
    ]);

    $response = $this->postJson("http://{$this->domain}/api-tenant/order/filter", [
        'search' => 'Matías',
    ]);

    $response->assertStatus(200)
        ->assertJsonPath('status', 'success')
        ->assertJsonCount(1, 'data');
});

it('POST /api-tenant/order/{id}/status and /cancel update order lifecycle', function () {
    $createRes = $this->postJson("http://{$this->domain}/api-tenant/order/create", [
        'customer_id' => $this->customer->id()->value(),
        'payment_method' => 'transfer',
        'items' => [
            [
                'product_id' => $this->product->id,
                'product_name' => $this->product->name,
                'sku' => $this->product->sku,
                'price' => 49.90,
                'quantity' => 1,
            ],
        ],
    ]);

    $orderId = $createRes->json('data.id');

    // 1. Confirm
    $confirmRes = $this->postJson("http://{$this->domain}/api-tenant/order/{$orderId}/status", [
        'status' => 'confirmed',
    ]);
    $confirmRes->assertStatus(200)
        ->assertJsonPath('data.status', 'confirmed');

    // 2. Process
    $processRes = $this->postJson("http://{$this->domain}/api-tenant/order/{$orderId}/status", [
        'status' => 'processing',
    ]);
    $processRes->assertStatus(200)
        ->assertJsonPath('data.status', 'processing');

    // 3. Ship
    $shipRes = $this->postJson("http://{$this->domain}/api-tenant/order/{$orderId}/status", [
        'status' => 'shipped',
        'shipping_method' => 'Chilexpress',
    ]);
    $shipRes->assertStatus(200)
        ->assertJsonPath('data.status', 'shipped')
        ->assertJsonPath('data.shipping_method', 'Chilexpress');

    // 4. Deliver
    $deliverRes = $this->postJson("http://{$this->domain}/api-tenant/order/{$orderId}/status", [
        'status' => 'delivered',
    ]);
    $deliverRes->assertStatus(200)
        ->assertJsonPath('data.status', 'delivered');

    // 5. Update payment status
    $paymentRes = $this->postJson("http://{$this->domain}/api-tenant/order/{$orderId}/payment-status", [
        'payment_status' => 'paid',
    ]);
    $paymentRes->assertStatus(200)
        ->assertJsonPath('data.payment_status', 'paid');
});

it('GET /api-tenant/order/metrics returns sales and orders summary', function () {
    $response = $this->getJson("http://{$this->domain}/api-tenant/order/metrics");

    $response->assertStatus(200)
        ->assertJsonPath('status', 'success')
        ->assertJsonStructure([
            'data' => [
                'total_orders',
                'pending_orders',
                'processing_orders',
                'completed_orders',
                'total_sales_amount',
                'average_order_value',
            ],
        ]);
});
