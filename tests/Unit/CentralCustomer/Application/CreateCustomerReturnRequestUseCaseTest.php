<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Src\CentralCustomer\Application\UseCases\CreateCustomerReturnRequestUseCase;
use Src\CentralCustomer\Infrastructure\Eloquent\Models\CentralCustomer;
use Src\CentralCustomer\Infrastructure\Eloquent\Models\CustomerReturnRequest;
use Src\Order\Infrastructure\Eloquent\Models\CentralOrder;
use Src\Order\Infrastructure\Eloquent\Models\CentralOrderItem;
use Src\Tenant\Infrastructure\Eloquent\Models\Tenant as ModelsTenant;
use Stancl\Tenancy\Bootstrappers\DatabaseTenancyBootstrapper;
use Stancl\Tenancy\Events\TenantCreated;
use Stancl\Tenancy\Events\TenantDeleted;

uses(Tests\TestCase::class);

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

    if (! ModelsTenant::where('id', 'store-returns')->exists()) {
        ModelsTenant::create([
            'id' => 'store-returns',
            'name' => 'Store Returns',
            'slug' => 'store-returns',
            'status' => 'active',
            'request' => 'approved',
        ]);
    }
});

test('CreateCustomerReturnRequestUseCase creates a return request for purchased product', function () {
    $customer = CentralCustomer::create([
        'id' => (string) Str::uuid(),
        'name' => 'Return User',
        'email' => 'return_'.bin2hex(random_bytes(3)).'@example.com',
        'password' => 'secret',
    ]);

    $order = CentralOrder::create([
        'id' => (string) Str::uuid(),
        'order_number' => 'ORD-2026-RET100',
        'customer_id' => $customer->id,
        'customer_name' => $customer->name,
        'customer_email' => $customer->email,
        'subtotal' => 45.00,
        'total' => 45.00,
        'status' => 'completed',
        'payment_status' => 'paid',
    ]);

    CentralOrderItem::create([
        'id' => (string) Str::uuid(),
        'central_order_id' => $order->id,
        'tenant_id' => 'store-returns',
        'product_id' => 'prod-123',
        'product_name' => 'Camisa Polo Talla M',
        'price' => 45.00,
        'quantity' => 1,
        'total' => 45.00,
    ]);

    $useCase = new CreateCustomerReturnRequestUseCase;
    $result = $useCase->execute($customer->id, [
        'order_id' => $order->id,
        'product_id' => 'prod-123',
        'reason' => 'Talla incorrecta',
        'description' => 'La talla M queda muy grande, solicito cambio por talla S.',
        'photos' => ['https://owomarket.local/storage/returns/photo1.jpg'],
    ]);

    expect($result)->toBeInstanceOf(CustomerReturnRequest::class);
    expect($result->status)->toBe('requested');
    expect($result->product_name)->toBe('Camisa Polo Talla M');
    expect($result->reason)->toBe('Talla incorrecta');
});
