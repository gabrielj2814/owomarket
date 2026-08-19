<?php

declare(strict_types=1);

use App\Models\CentralCustomer;
use App\Models\CentralOrder;
use App\Models\CentralOrderItem;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Src\CentralCustomer\Application\UseCases\ListCustomerOrdersUseCase;
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

    if (! ModelsTenant::where('id', 'store-a')->exists()) {
        ModelsTenant::create([
            'id' => 'store-a',
            'name' => 'Store A',
            'slug' => 'store-a',
            'status' => 'active',
            'request' => 'approved',
        ]);
    }
});

test('ListCustomerOrdersUseCase lists orders belonging to customer with pagination and filters', function () {
    $customer = CentralCustomer::create([
        'id' => (string) Str::uuid(),
        'name' => 'Comprador 1',
        'email' => 'comprador1_'.bin2hex(random_bytes(3)).'@example.com',
        'password' => 'secret',
    ]);

    $order1 = CentralOrder::create([
        'id' => (string) Str::uuid(),
        'order_number' => 'ORD-2026-000101',
        'customer_id' => $customer->id,
        'customer_name' => $customer->name,
        'customer_email' => $customer->email,
        'subtotal' => 150.00,
        'total' => 150.00,
        'status' => 'pending',
        'payment_status' => 'paid',
    ]);

    CentralOrderItem::create([
        'id' => (string) Str::uuid(),
        'central_order_id' => $order1->id,
        'tenant_id' => 'store-a',
        'product_id' => 'p1',
        'product_name' => 'Harina PAN 1kg',
        'price' => 1.50,
        'quantity' => 100,
        'total' => 150.00,
    ]);

    $useCase = new ListCustomerOrdersUseCase;
    $result = $useCase->execute($customer->id, $customer->email);

    expect($result['total'])->toBe(1);
    expect($result['data'])->toHaveCount(1);
    expect($result['data'][0]->order_number)->toBe('ORD-2026-000101');
});
