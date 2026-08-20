<?php

declare(strict_types=1);

use App\Models\CentralCustomer;
use App\Models\CentralOrder;
use App\Models\CentralOrderItem;
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

    if (! Schema::hasTable('tenants')) {
        Schema::create('tenants', function ($table) {
            $table->uuid('id')->primary();
            $table->string('name')->nullable();
            $table->string('slug')->nullable();
            $table->string('status')->nullable();
            $table->string('request')->nullable();
            $table->json('data')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }
    if (! Schema::hasTable('central_customers')) {
        (require base_path('database/migrations/2026_08_19_000001_create_central_customers_tables.php'))->up();
    }
    if (! Schema::hasTable('central_orders')) {
        (require base_path('database/migrations/2026_08_19_000004_create_central_orders_tables.php'))->up();
    }

    if (! ModelsTenant::where('id', 'tienda-test')->exists()) {
        ModelsTenant::create([
            'id' => 'tienda-test',
            'name' => 'Tienda Test',
            'slug' => 'tienda-test',
            'status' => 'active',
            'request' => 'approved',
        ]);
    }
});

test('GET /api/central/customer/orders returns customer orders with items', function () {
    $customer = CentralCustomer::create([
        'id' => (string) Str::uuid(),
        'name' => 'API Orders User',
        'email' => 'api_orders_'.bin2hex(random_bytes(3)).'@example.com',
        'password' => 'secret',
    ]);

    $order = CentralOrder::create([
        'id' => (string) Str::uuid(),
        'order_number' => 'ORD-2026-777666',
        'customer_id' => $customer->id,
        'customer_name' => $customer->name,
        'customer_email' => $customer->email,
        'subtotal' => 200.00,
        'total' => 200.00,
        'status' => 'paid',
        'payment_status' => 'paid',
    ]);

    $response = $this->getJson("/api/central/customer/orders?customer_id={$customer->id}");

    $response->assertStatus(200)
        ->assertJson([
            'code' => 200,
            'status' => 'success',
        ])
        ->assertJsonPath('meta.total', 1)
        ->assertJsonPath('data.0.order_number', 'ORD-2026-777666');
});

test('GET /api/central/customer/orders/{id}/tracking returns live tracking timeline', function () {
    $customer = CentralCustomer::create([
        'id' => (string) Str::uuid(),
        'name' => 'API Tracking User',
        'email' => 'api_track_'.bin2hex(random_bytes(3)).'@example.com',
        'password' => 'secret',
    ]);

    $order = CentralOrder::create([
        'id' => (string) Str::uuid(),
        'order_number' => 'ORD-2026-112233',
        'customer_id' => $customer->id,
        'customer_name' => $customer->name,
        'customer_email' => $customer->email,
        'subtotal' => 50.00,
        'total' => 50.00,
        'status' => 'processing',
        'payment_status' => 'paid',
        'metadata' => [
            'courier' => 'Zoom Envíos',
            'tracking_number' => 'ZM-99887766',
        ],
    ]);

    $response = $this->getJson("/api/central/customer/orders/{$order->id}/tracking?customer_id={$customer->id}");

    $response->assertStatus(200)
        ->assertJson([
            'code' => 200,
            'status' => 'success',
            'data' => [
                'order_number' => 'ORD-2026-112233',
                'courier' => 'Zoom Envíos',
                'tracking_number' => 'ZM-99887766',
            ],
        ]);
});

test('GET /api/central/customer/invoices returns invoice listing with BCV rate and totals', function () {
    $customer = CentralCustomer::create([
        'id' => (string) Str::uuid(),
        'name' => 'Invoice API User',
        'email' => 'inv_api_'.bin2hex(random_bytes(3)).'@example.com',
        'password' => 'secret',
    ]);

    $order = CentralOrder::create([
        'id' => (string) Str::uuid(),
        'order_number' => 'ORD-2026-445566',
        'customer_id' => $customer->id,
        'customer_name' => $customer->name,
        'customer_email' => $customer->email,
        'subtotal' => 100.00,
        'total' => 100.00,
        'payment_method' => 'pago_movil',
        'status' => 'paid',
        'payment_status' => 'paid',
        'payment_details' => [
            'rate_bcv' => 775.3356,
            'total_bs' => 77533.56,
            'reference_number' => 'REF-987654',
        ],
    ]);

    $response = $this->getJson("/api/central/customer/invoices?customer_id={$customer->id}");

    $response->assertStatus(200)
        ->assertJson([
            'code' => 200,
            'status' => 'success',
        ])
        ->assertJsonPath('data.0.invoice_number', 'FAC-2026-445566')
        ->assertJsonPath('data.0.total_usd', 100)
        ->assertJsonPath('data.0.total_ves', 77533.56)
        ->assertJsonPath('data.0.exchange_rate_bcv', 775.3356);
});

test('GET /api/central/customer/invoices/{id}/pdf downloads invoice PDF with 200 response', function () {
    $customer = CentralCustomer::create([
        'id' => (string) Str::uuid(),
        'name' => 'PDF Customer',
        'email' => 'pdf_cust_'.bin2hex(random_bytes(3)).'@example.com',
        'password' => 'secret',
    ]);

    $order = CentralOrder::create([
        'id' => (string) Str::uuid(),
        'order_number' => 'ORD-2026-888999',
        'customer_id' => $customer->id,
        'customer_name' => $customer->name,
        'customer_email' => $customer->email,
        'subtotal' => 120.00,
        'total' => 120.00,
        'payment_method' => 'pago_movil',
        'status' => 'paid',
        'payment_status' => 'paid',
        'payment_details' => [
            'rate_bcv' => 775.3356,
            'total_bs' => 93040.27,
            'reference_number' => 'REF-12345678',
        ],
    ]);

    CentralOrderItem::create([
        'id' => (string) Str::uuid(),
        'central_order_id' => $order->id,
        'tenant_id' => 'tienda-test',
        'product_id' => 'p99',
        'product_name' => 'Café Gourmet 500g',
        'price' => 6.00,
        'quantity' => 20,
        'total' => 120.00,
    ]);

    $response = $this->get("/api/central/customer/invoices/{$order->id}/pdf?customer_id={$customer->id}");

    $response->assertStatus(200);
    expect($response->headers->get('content-type'))->toBe('application/pdf');
    expect($response->headers->get('content-disposition'))->toContain('attachment; filename="Factura-FAC-2026-888999.pdf"');

    // Also download directly without query params
    $directResponse = $this->get("/api/central/customer/invoices/{$order->id}/pdf");
    $directResponse->assertStatus(200);
    expect($directResponse->headers->get('content-type'))->toBe('application/pdf');

    // Forbidden when wrong customer_id is passed
    $wrongCustomer = (string) Str::uuid();
    $forbiddenResponse = $this->get("/api/central/customer/invoices/{$order->id}/pdf?customer_id={$wrongCustomer}");
    $forbiddenResponse->assertStatus(403);
});
