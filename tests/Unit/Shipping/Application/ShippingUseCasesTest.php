<?php

declare(strict_types=1);

use Src\Shipping\Application\Contracts\ShippingRepositoryInterface;
use Src\Shipping\Application\UseCase\CalculateShippingOptionsUseCase;
use Src\Shipping\Application\UseCase\CreateShippingRateUseCase;
use Src\Shipping\Application\UseCase\CreateShippingZoneUseCase;
use Src\Shipping\Domain\Entities\ShippingRate;
use Src\Shipping\Domain\Entities\ShippingZone;
use Src\Shipping\Domain\ValueObjects\ShippingRateCost;
use Src\Shipping\Domain\ValueObjects\ShippingRateId;
use Src\Shipping\Domain\ValueObjects\ShippingRateName;
use Src\Shipping\Domain\ValueObjects\ShippingRateType;
use Src\Shipping\Domain\ValueObjects\ShippingStatus;
use Src\Shipping\Domain\ValueObjects\ShippingZoneId;
use Src\Shipping\Domain\ValueObjects\ShippingZoneName;

describe('Shipping Use Cases Unit Tests', function () {
    test('CreateShippingZoneUseCase creates a new shipping zone', function () {
        $repository = Mockery::mock(ShippingRepositoryInterface::class);

        $savedZone = new ShippingZone(
            id: ShippingZoneId::fromString('a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11'),
            name: ShippingZoneName::make('Zona Norte'),
            countries: ['MX'],
            states: ['NL', 'COAH'],
            postalCodes: null,
            priority: 1,
            isActive: ShippingStatus::active()
        );

        $repository->shouldReceive('saveZone')
            ->once()
            ->andReturn($savedZone);

        $useCase = new CreateShippingZoneUseCase($repository);
        $result = $useCase->execute(
            name: 'Zona Norte',
            countries: ['MX'],
            states: ['NL', 'COAH'],
            priority: 1
        );

        expect($result->name()->value())->toBe('Zona Norte')
            ->and($result->states())->toBe(['NL', 'COAH']);
    });

    test('CreateShippingRateUseCase creates a rate attached to a zone', function () {
        $repository = Mockery::mock(ShippingRepositoryInterface::class);

        $zone = new ShippingZone(
            id: ShippingZoneId::fromString('a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11'),
            name: ShippingZoneName::make('Zona Centro'),
            countries: ['MX']
        );

        $savedRate = new ShippingRate(
            id: ShippingRateId::fromString('b0eebc99-9c0b-4ef8-bb6d-6bb9bd380a22'),
            shippingZoneId: ShippingZoneId::fromString('a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11'),
            name: ShippingRateName::make('Express'),
            type: ShippingRateType::flat(),
            cost: ShippingRateCost::fromFloat(15.0)
        );

        $repository->shouldReceive('findZoneById')
            ->once()
            ->andReturn($zone);

        $repository->shouldReceive('saveRate')
            ->once()
            ->andReturn($savedRate);

        $useCase = new CreateShippingRateUseCase($repository);
        $result = $useCase->execute(
            shippingZoneId: 'a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11',
            name: 'Express',
            type: 'flat',
            cost: 15.0
        );

        expect($result->name()->value())->toBe('Express')
            ->and($result->cost()->value())->toBe(15.0);
    });

    test('CalculateShippingOptionsUseCase selects and calculates applicable rates', function () {
        $repository = Mockery::mock(ShippingRepositoryInterface::class);

        $rate1 = new ShippingRate(
            id: ShippingRateId::fromString('b0eebc99-9c0b-4ef8-bb6d-6bb9bd380a22'),
            shippingZoneId: ShippingZoneId::fromString('a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11'),
            name: ShippingRateName::make('Estándar'),
            type: ShippingRateType::flat(),
            cost: ShippingRateCost::fromFloat(5.0)
        );

        $rate2 = new ShippingRate(
            id: ShippingRateId::fromString('c0eebc99-9c0b-4ef8-bb6d-6bb9bd380a33'),
            shippingZoneId: ShippingZoneId::fromString('a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11'),
            name: ShippingRateName::make('Gratis sobre 50'),
            type: ShippingRateType::free(),
            cost: ShippingRateCost::fromFloat(0.0),
            minValue: 50.0
        );

        $zone = new ShippingZone(
            id: ShippingZoneId::fromString('a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11'),
            name: ShippingZoneName::make('Zona MX'),
            countries: ['MX'],
            rates: [$rate1, $rate2]
        );

        $repository->shouldReceive('findMatchingZones')
            ->once()
            ->with('MX', null, null)
            ->andReturn([$zone]);

        $useCase = new CalculateShippingOptionsUseCase($repository);
        $result = $useCase->execute(
            orderValue: 60.0,
            country: 'MX'
        );

        expect(count($result->options))->toBe(2)
            ->and($result->recommendedOption['cost'])->toBe(0.0);
    });
});
