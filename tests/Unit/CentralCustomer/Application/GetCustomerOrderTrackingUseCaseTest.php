<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Src\CentralCustomer\Application\UseCases\GetCustomerOrderTrackingUseCase;
use Src\CentralCustomer\Infrastructure\Eloquent\Models\CentralCustomer;
use Src\Order\Infrastructure\Eloquent\Models\CentralOrder;
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

    if (! Schema::hasTable('central_customers')) {
        (require base_path('database/migrations/2026_08_19_000001_create_central_customers_tables.php'))->up();
    }
    if (! Schema::hasTable('central_orders')) {
        (require base_path('database/migrations/2026_08_19_000004_create_central_orders_tables.php'))->up();
    }
});

test('GetCustomerOrderTrackingUseCase generates full delivery timeline and tracking info', function () {
    $customer = CentralCustomer::create([
        'id' => (string) Str::uuid(),
        'name' => 'Tracking User',
        'email' => 'tracking_'.bin2hex(random_bytes(3)).'@example.com',
        'password' => 'secret',
    ]);

    $order = CentralOrder::create([
        'id' => (string) Str::uuid(),
        'order_number' => 'ORD-2026-999888',
        'customer_id' => $customer->id,
        'customer_name' => $customer->name,
        'customer_email' => $customer->email,
        'subtotal' => 80.00,
        'total' => 80.00,
        'status' => 'processing',
        'payment_status' => 'paid',
        'metadata' => [
            'courier' => 'MRW Express',
            'tracking_number' => 'MRW-5544332211',
        ],
    ]);

    $useCase = new GetCustomerOrderTrackingUseCase;
    $result = $useCase->execute($customer->id, $order->id);

    expect($result['order_number'])->toBe('ORD-2026-999888');
    expect($result['courier'])->toBe('MRW Express');
    expect($result['tracking_number'])->toBe('MRW-5544332211');
    expect($result['timeline'])->toHaveCount(5);
    expect($result['current_step'])->toBe(4);
});
