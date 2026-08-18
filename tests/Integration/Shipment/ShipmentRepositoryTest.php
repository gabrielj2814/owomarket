<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Src\Customer\Infrastructure\Eloquent\Models\Customer as EloquentCustomer;
use Src\Order\Infrastructure\Eloquent\Models\Order as EloquentOrder;
use Src\Shipment\Application\DTOs\FilterShipmentsCriteria;
use Src\Shipment\Domain\Entities\Shipment as DomainShipment;
use Src\Shipment\Domain\ValueObjects\TrackingNumber;
use Src\Shipment\Infrastructure\Eloquent\Repositories\EloquentShipmentRepository;
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
        'id'      => $tenantId,
        'name'    => 'Shipment Tenant Store',
        'slug'    => $tenantId,
        'status'  => 'active',
        'request' => 'approved',
    ]);
    $this->domain = "{$tenantId}.localhost";
    $this->tenant->domains()->create([
        'id'     => (string) Str::uuid(),
        'domain' => $this->domain,
    ]);

    tenancy()->initialize($this->tenant);
    $this->repository = new EloquentShipmentRepository();
});

afterEach(function () {
    if (tenancy()->initialized) {
        tenancy()->end();
    }
});

it('saves and retrieves shipment with automatic order status sync', function () {
    // 1. Create Customer
    $customer = EloquentCustomer::create([
        'id'        => (string) Str::uuid(),
        'name'      => 'Juan Pérez',
        'email'     => 'juan.perez@test.cl',
        'is_active' => true,
    ]);

    // 2. Create Eloquent Order
    $order = EloquentOrder::create([
        'id'              => (string) Str::uuid(),
        'order_number'    => 'ORD-20260818-SHIP01',
        'customer_id'     => $customer->id,
        'status'          => 'processing',
        'payment_status'  => 'paid',
        'payment_method'  => 'webpay',
        'currency'        => 'USD',
        'subtotal'        => 100.00,
        'total'           => 100.00,
    ]);

    // 3. Create and save pending shipment
    $domainShipment = DomainShipment::create(
        orderId: $order->id,
        carrier: 'Chilexpress',
        service: 'Express 24h',
        cost: 15.00,
        notes: 'Envío frágil'
    );

    $saved = $this->repository->save($domainShipment);
    expect($saved->id()->value())->toBe($domainShipment->id()->value())
        ->and($saved->carrier()->value())->toBe('Chilexpress')
        ->and($saved->isPending())->toBeTrue();

    // 4. Assign tracking and verify order synced to 'shipped'
    $saved->assignTrackingNumber(new TrackingNumber('CHX-99887766'));
    $updated = $this->repository->save($saved);

    expect($updated->isInTransit())->toBeTrue()
        ->and($updated->trackingNumber()?->value())->toBe('CHX-99887766');

    $order->refresh();
    expect($order->status)->toBe('shipped')
        ->and($order->shipping_method)->toBe('Chilexpress');

    // 5. Mark as delivered and verify order synced to 'delivered'
    $updated->markAsDelivered();
    $delivered = $this->repository->save($updated);

    expect($delivered->isDelivered())->toBeTrue();

    $order->refresh();
    expect($order->status)->toBe('delivered')
        ->and($order->delivered_at)->not->toBeNull();

    // 6. Find by ID and by Tracking Number
    $foundById = $this->repository->findById($delivered->id());
    expect($foundById)->not->toBeNull()
        ->and($foundById->id()->value())->toBe($delivered->id()->value());

    $foundByTrack = $this->repository->findByTrackingNumber(new TrackingNumber('CHX-99887766'));
    expect($foundByTrack)->not->toBeNull()
        ->and($foundByTrack->id()->value())->toBe($delivered->id()->value());

    // 7. Find by Order ID
    $orderShipments = $this->repository->findByOrderId($order->id);
    expect($orderShipments)->toHaveCount(1)
        ->and($orderShipments[0]->id()->value())->toBe($delivered->id()->value());
});

it('filters shipments and retrieves metrics correctly in tenant database', function () {
    $customer = EloquentCustomer::create([
        'id'        => (string) Str::uuid(),
        'name'      => 'Maria López',
        'email'     => 'maria@test.cl',
        'is_active' => true,
    ]);

    $order1 = EloquentOrder::create([
        'id'             => (string) Str::uuid(),
        'order_number'   => 'ORD-MTRX-01',
        'customer_id'    => $customer->id,
        'status'         => 'processing',
        'payment_status' => 'paid',
        'payment_method' => 'webpay',
        'currency'       => 'USD',
        'subtotal'       => 50.00,
        'total'          => 50.00,
    ]);

    $order2 = EloquentOrder::create([
        'id'             => (string) Str::uuid(),
        'order_number'   => 'ORD-MTRX-02',
        'customer_id'    => $customer->id,
        'status'         => 'processing',
        'payment_status' => 'paid',
        'payment_method' => 'cash',
        'currency'       => 'USD',
        'subtotal'       => 80.00,
        'total'          => 80.00,
    ]);

    // Shipment 1: In Transit ($20)
    $ship1 = DomainShipment::create(
        orderId: $order1->id,
        carrier: 'DHL',
        service: 'Express',
        cost: 20.00,
        trackingNumber: 'DHL-112233'
    );
    $this->repository->save($ship1);

    // Shipment 2: Pending ($10)
    $ship2 = DomainShipment::create(
        orderId: $order2->id,
        carrier: 'Starken',
        service: 'Standard',
        cost: 10.00
    );
    $this->repository->save($ship2);

    // Filter by carrier
    $dhlFilter = $this->repository->filter(new FilterShipmentsCriteria(carrier: 'DHL'));
    expect($dhlFilter->total)->toBe(1)
        ->and($dhlFilter->items[0]->carrier()->value())->toBe('DHL');

    // Metrics verification
    $metrics = $this->repository->getMetrics();
    expect($metrics->totalShipments)->toBe(2)
        ->and($metrics->inTransitShipments)->toBe(1)
        ->and($metrics->pendingShipments)->toBe(1)
        ->and($metrics->deliveredShipments)->toBe(0)
        ->and($metrics->totalShippingCost)->toBe(30.00);
});
