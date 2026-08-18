<?php

declare(strict_types=1);

use Src\Shipment\Domain\ValueObjects\Carrier;
use Src\Shipment\Domain\ValueObjects\ShipmentCost;
use Src\Shipment\Domain\ValueObjects\ShipmentId;
use Src\Shipment\Domain\ValueObjects\ShipmentServiceType;
use Src\Shipment\Domain\ValueObjects\ShipmentStatus;
use Src\Shipment\Domain\ValueObjects\TrackingNumber;

it('creates valid ShipmentId and compares equality', function () {
    $id1 = ShipmentId::random();
    expect($id1->value())->not->toBeEmpty();

    $id2 = ShipmentId::fromString($id1->value());
    expect($id1->equals($id2))->toBeTrue();
});

it('throws exception on invalid ShipmentId UUID format', function () {
    new ShipmentId('invalid-uuid-123');
})->throws(InvalidArgumentException::class);

it('creates valid TrackingNumber and trims whitespace', function () {
    $tracking = new TrackingNumber('  CHI-99887766  ');
    expect($tracking->value())->toBe('CHI-99887766')
        ->and((string) $tracking)->toBe('CHI-99887766');

    $tracking2 = TrackingNumber::fromString('CHI-99887766');
    expect($tracking->equals($tracking2))->toBeTrue();
});

it('throws exception on empty or too short/long TrackingNumber', function () {
    new TrackingNumber(' ');
})->throws(InvalidArgumentException::class);

it('creates valid Carrier and checks case-insensitive equality', function () {
    $carrier1 = new Carrier('Chilexpress');
    $carrier2 = new Carrier('CHILEXPRESS');
    expect($carrier1->equals($carrier2))->toBeTrue()
        ->and($carrier1->value())->toBe('Chilexpress');
});

it('throws exception on empty Carrier', function () {
    new Carrier('');
})->throws(InvalidArgumentException::class);

it('creates valid ShipmentServiceType and validates length', function () {
    $service = new ShipmentServiceType('Express 24h');
    expect($service->value())->toBe('Express 24h');

    $service2 = ShipmentServiceType::fromString('express 24h');
    expect($service->equals($service2))->toBeTrue();
});

it('creates valid ShipmentCost with rounding and rejects negative amounts', function () {
    $cost = new ShipmentCost(15.456);
    expect($cost->amount())->toBe(15.46)
        ->and((string) $cost)->toBe('15.46');

    $cost2 = ShipmentCost::fromFloat(15.46);
    expect($cost->equals($cost2))->toBeTrue();

    $zero = ShipmentCost::zero();
    expect($zero->amount())->toBe(0.0);
});

it('throws exception on negative ShipmentCost', function () {
    new ShipmentCost(-5.0);
})->throws(InvalidArgumentException::class);

it('parses valid ShipmentStatus enum and throws on invalid string', function () {
    expect(ShipmentStatus::fromString('pending'))->toBe(ShipmentStatus::PENDING)
        ->and(ShipmentStatus::fromString('in_transit'))->toBe(ShipmentStatus::IN_TRANSIT)
        ->and(ShipmentStatus::fromString('delivered'))->toBe(ShipmentStatus::DELIVERED);

    ShipmentStatus::fromString('unknown_status');
})->throws(InvalidArgumentException::class);
