<?php

declare(strict_types=1);

use Ramsey\Uuid\Uuid;
use Src\Shipment\Domain\Entities\Shipment;
use Src\Shipment\Domain\Exceptions\ShipmentAlreadyDeliveredException;
use Src\Shipment\Domain\ValueObjects\Carrier;
use Src\Shipment\Domain\ValueObjects\ShipmentCost;
use Src\Shipment\Domain\ValueObjects\ShipmentServiceType;
use Src\Shipment\Domain\ValueObjects\ShipmentStatus;
use Src\Shipment\Domain\ValueObjects\TrackingNumber;

it('creates a shipment in pending status without tracking number', function () {
    $orderId = Uuid::uuid4()->toString();
    $shipment = Shipment::create(
        orderId: $orderId,
        carrier: 'Starken',
        service: 'Standard Delivery',
        cost: 12.50,
        notes: 'Dejar en conserjería'
    );

    expect($shipment->id()->value())->not->toBeEmpty()
        ->and($shipment->orderId())->toBe($orderId)
        ->and($shipment->carrier()->value())->toBe('Starken')
        ->and($shipment->service()->value())->toBe('Standard Delivery')
        ->and($shipment->cost()->amount())->toBe(12.50)
        ->and($shipment->trackingNumber())->toBeNull()
        ->and($shipment->status())->toBe(ShipmentStatus::PENDING)
        ->and($shipment->isPending())->toBeTrue()
        ->and($shipment->isInTransit())->toBeFalse()
        ->and($shipment->isDelivered())->toBeFalse();
});

it('creates a shipment directly in transit if tracking number is provided', function () {
    $orderId = Uuid::uuid4()->toString();
    $shipment = Shipment::create(
        orderId: $orderId,
        carrier: 'DHL Express',
        service: 'Next Day',
        cost: 25.00,
        trackingNumber: 'DHL-12345678'
    );

    expect($shipment->status())->toBe(ShipmentStatus::IN_TRANSIT)
        ->and($shipment->isInTransit())->toBeTrue()
        ->and($shipment->trackingNumber()?->value())->toBe('DHL-12345678')
        ->and($shipment->shippedAt())->not->toBeNull();
});

it('transitions shipment from pending to in_transit when tracking is assigned', function () {
    $shipment = Shipment::create(
        orderId: Uuid::uuid4()->toString(),
        carrier: 'Chilexpress',
        service: 'Overnight'
    );

    expect($shipment->isPending())->toBeTrue();

    $shipment->assignTrackingNumber(new TrackingNumber('CHX-99887711'));

    expect($shipment->isInTransit())->toBeTrue()
        ->and($shipment->trackingNumber()?->value())->toBe('CHX-99887711')
        ->and($shipment->shippedAt())->not->toBeNull();
});

it('transitions shipment to delivered when marked as delivered', function () {
    $shipment = Shipment::create(
        orderId: Uuid::uuid4()->toString(),
        carrier: 'FedEx',
        service: 'Priority',
        trackingNumber: 'FDX-77665544'
    );

    expect($shipment->isInTransit())->toBeTrue();

    $shipment->markAsDelivered();

    expect($shipment->isDelivered())->toBeTrue()
        ->and($shipment->status())->toBe(ShipmentStatus::DELIVERED)
        ->and($shipment->deliveredAt())->not->toBeNull();
});

it('throws exception when modifying already delivered shipment', function () {
    $shipment = Shipment::create(
        orderId: Uuid::uuid4()->toString(),
        carrier: 'Starken',
        service: 'Standard'
    );
    $shipment->markAsDelivered();

    $shipment->assignTrackingNumber(new TrackingNumber('NEW-TRACKING-123'));
})->throws(ShipmentAlreadyDeliveredException::class);

it('exports shipment entity to array format correctly', function () {
    $orderId = Uuid::uuid4()->toString();
    $shipment = Shipment::create(
        orderId: $orderId,
        carrier: new Carrier('Blue Express'),
        service: new ShipmentServiceType('Same Day'),
        cost: new ShipmentCost(8.99),
        notes: 'Piso 4 Oficina A',
        metadata: ['courier_code' => 'BLU-01']
    );

    $array = $shipment->toArray();

    expect($array['order_id'])->toBe($orderId)
        ->and($array['carrier'])->toBe('Blue Express')
        ->and($array['service'])->toBe('Same Day')
        ->and($array['cost'])->toBe(8.99)
        ->and($array['status'])->toBe('pending')
        ->and($array['notes'])->toBe('Piso 4 Oficina A')
        ->and($array['metadata'])->toBe(['courier_code' => 'BLU-01']);
});
