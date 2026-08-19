<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
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
    if (! Schema::hasTable('billing_profiles')) {
        (require base_path('database/migrations/tenant/2026_08_18_000001_create_billing_profiles.php'))->up();
    }
    if (! Schema::hasTable('invoices')) {
        (require base_path('database/migrations/tenant/2026_08_18_000002_create_invoices.php'))->up();
    }
    if (! Schema::hasTable('invoice_items')) {
        (require base_path('database/migrations/tenant/2026_08_18_000003_create_invoice_items.php'))->up();
    }

    $tenantId = 't_'.bin2hex(random_bytes(4));
    $this->tenant = ModelsTenant::create([
        'id' => $tenantId,
        'name' => 'Order E2E Store',
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
});

afterEach(function () {
    if (tenancy()->initialized) {
        tenancy()->end();
    }
});

it('executes full order lifecycle from creation to delivery and invoicing', function () {
    // 1. Initial metrics: 0 orders, 0 sales
    $initialMetrics = $this->getJson("http://{$this->domain}/api-tenant/order/metrics");
    $initialMetrics->assertStatus(200)
        ->assertJsonPath('status', 'success')
        ->assertJsonPath('data.total_orders', 0)
        ->assertJsonPath('data.total_sales_amount', 0);

    // 2. Setup Customer via Customer API
    $customerRes = $this->postJson("http://{$this->domain}/api-tenant/customer/create", [
        'name' => 'Rodrigo Bravo',
        'email' => 'rodrigo.bravo@test.cl',
        'phone' => '+56999887766',
        'is_active' => true,
        'addresses' => [
            [
                'first_name' => 'Rodrigo',
                'last_name' => 'Bravo',
                'address_line_1' => 'Av. Providencia 1234',
                'city' => 'Providencia',
                'state' => 'Región Metropolitana',
                'postal_code' => '7500000',
                'country' => 'Chile',
                'type' => 'both',
                'is_default' => true,
            ],
        ],
    ]);
    $customerRes->assertStatus(201);
    $customerId = $customerRes->json('data.id');

    // 3. Setup 2 Products via Eloquent
    $prod1 = \Src\Product\Infrastructure\Eloquent\Models\Product::create([
        'id' => (string) Str::uuid(),
        'name' => 'Monitor 4K 27 Pulgadas',
        'slug' => 'monitor-4k-27',
        'sku' => 'MON-4K-27',
        'price' => 399.99,
        'quantity' => 10,
        'is_visible' => true,
    ]);

    $prod2 = \Src\Product\Infrastructure\Eloquent\Models\Product::create([
        'id' => (string) Str::uuid(),
        'name' => 'Teclado Mecánico RGB',
        'slug' => 'teclado-mecanico-rgb',
        'sku' => 'KEY-MEC-RGB',
        'price' => 79.99,
        'quantity' => 25,
        'is_visible' => true,
    ]);

    // 4. Create Order: 1 Monitor ($399.99) + 2 Teclados ($159.98) = Subtotal $559.97
    // Tax: $106.39, Shipping: $15.00, Discount: $20.00 => Total: $661.36
    $createOrderRes = $this->postJson("http://{$this->domain}/api-tenant/order/create", [
        'customer_id' => $customerId,
        'payment_method' => 'webpay',
        'currency' => 'USD',
        'tax_amount' => 106.39,
        'shipping_amount' => 15.00,
        'discount_amount' => 20.00,
        'shipping_method' => 'Chilexpress Prioritario',
        'notes' => 'Entregar en conserjería',
        'customer_note' => 'Llamar antes de llegar',
        'items' => [
            [
                'product_id' => $prod1->id,
                'product_name' => $prod1->name,
                'sku' => $prod1->sku,
                'price' => 399.99,
                'quantity' => 1,
            ],
            [
                'product_id' => $prod2->id,
                'product_name' => $prod2->name,
                'sku' => $prod2->sku,
                'price' => 79.99,
                'quantity' => 2,
            ],
        ],
    ]);

    $createOrderRes->assertStatus(201)
        ->assertJsonPath('status', 'success')
        ->assertJsonPath('data.status', 'pending')
        ->assertJsonPath('data.payment_status', 'pending')
        ->assertJsonPath('data.customer_id', $customerId)
        ->assertJsonPath('data.subtotal', fn ($sub) => abs($sub - 559.97) < 0.01)
        ->assertJsonPath('data.total', fn ($tot) => abs($tot - 661.36) < 0.01)
        ->assertJsonCount(2, 'data.items');

    $orderId = $createOrderRes->json('data.id');
    $orderNumber = $createOrderRes->json('data.order_number');

    // 5. Consult Order by UUID and by Order Number
    $getById = $this->getJson("http://{$this->domain}/api-tenant/order/{$orderId}");
    $getById->assertStatus(200)
        ->assertJsonPath('data.id', $orderId)
        ->assertJsonPath('data.order_number', $orderNumber);

    $getByNum = $this->getJson("http://{$this->domain}/api-tenant/order/number/{$orderNumber}");
    $getByNum->assertStatus(200)
        ->assertJsonPath('data.id', $orderId);

    // 6. Confirm Order
    $confirmRes = $this->postJson("http://{$this->domain}/api-tenant/order/{$orderId}/status", [
        'status' => 'confirmed',
    ]);
    $confirmRes->assertStatus(200)
        ->assertJsonPath('data.status', 'confirmed');

    // 7. Process Order
    $processRes = $this->postJson("http://{$this->domain}/api-tenant/order/{$orderId}/status", [
        'status' => 'processing',
    ]);
    $processRes->assertStatus(200)
        ->assertJsonPath('data.status', 'processing');

    // 8. Update Payment Status to 'paid'
    $paymentRes = $this->postJson("http://{$this->domain}/api-tenant/order/{$orderId}/payment-status", [
        'payment_status' => 'paid',
    ]);
    $paymentRes->assertStatus(200)
        ->assertJsonPath('data.payment_status', 'paid');

    // 9. Cross-Module Invoicing Bridge: Create direct invoice for this order
    $invoiceRes = $this->postJson("http://{$this->domain}/api-tenant/billing/invoices", [
        'customer_name' => 'Rodrigo Bravo',
        'customer_email' => 'rodrigo.bravo@test.cl',
        'customer_tax_id' => '15.678.901-2',
        'customer_address_line_1' => 'Av. Providencia 1234',
        'customer_city' => 'Santiago',
        'customer_state' => 'RM',
        'customer_postal_code' => '7500000',
        'customer_country' => 'Chile',
        'payment_method' => 'webpay',
        'payment_status' => 'paid',
        'status' => 'issued',
        'currency' => 'USD',
        'notes' => "Factura generada desde Orden {$orderNumber}",
        'items' => [
            [
                'description' => 'Monitor 4K 27 Pulgadas',
                'quantity' => 1,
                'unit_price' => 399.99,
                'tax_rate' => 19.0,
                'discount_amount' => 0,
            ],
            [
                'description' => 'Teclado Mecánico RGB',
                'quantity' => 2,
                'unit_price' => 79.99,
                'tax_rate' => 19.0,
                'discount_amount' => 0,
            ],
        ],
    ]);
    $invoiceRes->assertStatus(201)
        ->assertJsonPath('status', 'success')
        ->assertJsonPath('data.status', 'issued');

    // 10. Ship Order
    $shipRes = $this->postJson("http://{$this->domain}/api-tenant/order/{$orderId}/status", [
        'status' => 'shipped',
        'shipping_method' => 'DHL Express',
    ]);
    $shipRes->assertStatus(200)
        ->assertJsonPath('data.status', 'shipped')
        ->assertJsonPath('data.shipping_method', 'DHL Express');

    // 11. Deliver Order
    $deliverRes = $this->postJson("http://{$this->domain}/api-tenant/order/{$orderId}/status", [
        'status' => 'delivered',
    ]);
    $deliverRes->assertStatus(200)
        ->assertJsonPath('data.status', 'delivered');

    // 12. Create a second order and test cancellation flow
    $order2Res = $this->postJson("http://{$this->domain}/api-tenant/order/create", [
        'customer_id' => $customerId,
        'payment_method' => 'cash',
        'items' => [
            [
                'product_id' => $prod2->id,
                'product_name' => $prod2->name,
                'sku' => $prod2->sku,
                'price' => 79.99,
                'quantity' => 1,
            ],
        ],
    ]);
    $order2Id = $order2Res->json('data.id');

    $cancelRes = $this->postJson("http://{$this->domain}/api-tenant/order/{$order2Id}/cancel", [
        'reason' => 'Cliente solicitó desistir de la compra',
    ]);
    $cancelRes->assertStatus(200)
        ->assertJsonPath('data.status', 'cancelled');

    // 13. Verify Final Metrics
    $finalMetrics = $this->getJson("http://{$this->domain}/api-tenant/order/metrics");
    $finalMetrics->assertStatus(200)
        ->assertJsonPath('data.total_orders', 2)
        ->assertJsonPath('data.completed_orders', 1)
        ->assertJsonPath('data.total_sales_amount', fn ($sales) => abs($sales - 661.36) < 0.01);
});
