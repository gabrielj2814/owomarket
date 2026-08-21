<?php

declare(strict_types=1);

use Src\CentralCustomer\Infrastructure\Eloquent\Models\CentralCustomer;
use Src\Order\Infrastructure\Eloquent\Models\CentralOrder;
use Src\Order\Infrastructure\Eloquent\Models\CentralOrderItem;
use Src\CentralCustomer\Infrastructure\Eloquent\Models\CustomerReturnRequest;
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
    if (! Schema::hasTable('customer_return_requests')) {
        (require base_path('database/migrations/2026_08_19_000010_create_customer_return_requests_table.php'))->up();
    }
    if (! Schema::hasTable('central_customer_wishlists')) {
        (require base_path('database/migrations/2026_08_19_000011_create_central_customer_wishlists_table.php'))->up();
    }

    if (! ModelsTenant::where('id', 'tienda-ret-wish')->exists()) {
        ModelsTenant::create([
            'id' => 'tienda-ret-wish',
            'name' => 'Tienda Ret Wish',
            'slug' => 'tienda-ret-wish',
            'status' => 'active',
            'request' => 'approved',
        ]);
    }
});

test('POST /api/central/customer/returns and GET /api/central/customer/returns manage RMA requests', function () {
    $customer = CentralCustomer::create([
        'id' => (string) Str::uuid(),
        'name' => 'Feature Return User',
        'email' => 'feat_ret_'.bin2hex(random_bytes(3)).'@example.com',
        'password' => 'secret',
    ]);

    $order = CentralOrder::create([
        'id' => (string) Str::uuid(),
        'order_number' => 'ORD-2026-RMA555',
        'customer_id' => $customer->id,
        'customer_name' => $customer->name,
        'customer_email' => $customer->email,
        'subtotal' => 60.00,
        'total' => 60.00,
        'status' => 'completed',
        'payment_status' => 'paid',
    ]);

    CentralOrderItem::create([
        'id' => (string) Str::uuid(),
        'central_order_id' => $order->id,
        'tenant_id' => 'tienda-ret-wish',
        'product_id' => 'p-ret-55',
        'product_name' => 'Teclado Mecánico RGB',
        'price' => 60.00,
        'quantity' => 1,
        'total' => 60.00,
    ]);

    // 1. Create return request
    $createRes = $this->postJson('/api/central/customer/returns', [
        'customer_id' => $customer->id,
        'order_id' => $order->id,
        'product_id' => 'p-ret-55',
        'reason' => 'Producto defectuoso',
        'description' => 'La tecla espaciadora no funciona correctamente.',
    ]);

    $createRes->assertStatus(200)
        ->assertJson([
            'code' => 200,
            'status' => 'success',
        ]);

    expect(CustomerReturnRequest::where('customer_id', $customer->id)->exists())->toBeTrue();

    // 2. List return requests
    $listRes = $this->getJson("/api/central/customer/returns?customer_id={$customer->id}");
    $listRes->assertStatus(200)
        ->assertJsonPath('data.0.reason', 'Producto defectuoso')
        ->assertJsonPath('data.0.product_name', 'Teclado Mecánico RGB');
});

test('GET /api/central/customer/coupons returns promotional coupons', function () {
    $response = $this->getJson('/api/central/customer/coupons');

    $response->assertStatus(200)
        ->assertJson([
            'code' => 200,
            'status' => 'success',
        ]);

    expect($response->json('data'))->not->toBeEmpty();
    expect($response->json('data.0.code'))->toBe('OWOPASS10');
});

test('POST /api/central/customer/wishlist/toggle and GET /api/central/customer/wishlist manage favorites', function () {
    $customer = CentralCustomer::create([
        'id' => (string) Str::uuid(),
        'name' => 'Feature Wish User',
        'email' => 'feat_wish_'.bin2hex(random_bytes(3)).'@example.com',
        'password' => 'secret',
    ]);

    // 1. Toggle Add
    $toggleRes1 = $this->postJson('/api/central/customer/wishlist/toggle', [
        'customer_id' => $customer->id,
        'product_id' => 'p-fav-99',
        'tenant_id' => 'tienda-ret-wish',
        'product_name' => 'Audífonos Bluetooth Pro',
        'product_price' => 35.00,
    ]);

    $toggleRes1->assertStatus(200)
        ->assertJsonPath('data.in_wishlist', true);

    // 2. List wishlist
    $listRes = $this->getJson("/api/central/customer/wishlist?customer_id={$customer->id}");
    $listRes->assertStatus(200)
        ->assertJsonPath('data.0.product_name', 'Audífonos Bluetooth Pro');

    // 3. Toggle Remove
    $toggleRes2 = $this->postJson('/api/central/customer/wishlist/toggle', [
        'customer_id' => $customer->id,
        'product_id' => 'p-fav-99',
        'tenant_id' => 'tienda-ret-wish',
        'product_name' => 'Audífonos Bluetooth Pro',
        'product_price' => 35.00,
    ]);

    $toggleRes2->assertStatus(200)
        ->assertJsonPath('data.in_wishlist', false);
});
