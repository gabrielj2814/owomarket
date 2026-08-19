<?php

declare(strict_types=1);

use Src\Shipping\Domain\Entities\ShippingRate;
use Src\Shipping\Domain\Entities\ShippingZone;
use Src\Shipping\Domain\ValueObjects\ShippingRateCost;
use Src\Shipping\Domain\ValueObjects\ShippingRateName;
use Src\Shipping\Domain\ValueObjects\ShippingRateType;
use Src\Shipping\Domain\ValueObjects\ShippingZoneId;
use Src\Shipping\Domain\ValueObjects\ShippingZoneName;

describe('Shipping Domain Unit Tests', function () {
    test('ShippingRateType validates allowed types', function () {
        $flat = ShippingRateType::flat();
        expect($flat->value())->toBe('flat')
            ->and($flat->isFlat())->toBeTrue();

        expect(fn () => ShippingRateType::fromString('unknown_type'))
            ->toThrow(InvalidArgumentException::class);
    });

    test('ShippingRateCost prevents negative values', function () {
        $cost = ShippingRateCost::fromFloat(15.5);
        expect($cost->value())->toBe(15.5);

        expect(fn () => ShippingRateCost::fromFloat(-5.0))
            ->toThrow(InvalidArgumentException::class);
    });

    test('ShippingRate appliesTo handles flat, free, price-based, and weight-based rules', function () {
        $zoneId = ShippingZoneId::fromString('a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11');

        $priceBasedRate = ShippingRate::create(
            shippingZoneId: $zoneId,
            name: ShippingRateName::make('Envío Estándar'),
            type: ShippingRateType::priceBased(),
            cost: ShippingRateCost::fromFloat(10.0),
            minValue: 50.0,
            maxValue: 200.0
        );

        expect($priceBasedRate->appliesTo(30.0))->toBeFalse()
            ->and($priceBasedRate->appliesTo(100.0))->toBeTrue()
            ->and($priceBasedRate->appliesTo(250.0))->toBeFalse();
    });

    test('ShippingZone matchesLocation matches countries, states, postal codes', function () {
        $zone = ShippingZone::create(
            name: ShippingZoneName::make('Zona Nacional'),
            countries: ['MX', 'US'],
            states: ['CDMX', 'JAL'],
            postalCodes: ['01000', '44100']
        );

        expect($zone->matchesLocation('MX', 'CDMX', '01000'))->toBeTrue()
            ->and($zone->matchesLocation('CO', 'CDMX', '01000'))->toBeFalse()
            ->and($zone->matchesLocation('MX', 'NL', '01000'))->toBeFalse();
    });
});
