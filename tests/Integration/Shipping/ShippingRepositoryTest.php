<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schema;
use Src\Shipping\Application\DTOs\ShippingZoneFilterCriteria;
use Src\Shipping\Domain\Entities\ShippingRate;
use Src\Shipping\Domain\Entities\ShippingZone;
use Src\Shipping\Domain\ValueObjects\ShippingRateCost;
use Src\Shipping\Domain\ValueObjects\ShippingRateName;
use Src\Shipping\Domain\ValueObjects\ShippingRateType;
use Src\Shipping\Domain\ValueObjects\ShippingZoneName;
use Src\Shipping\Infrastructure\Eloquent\Repositories\ShippingRepository;

beforeEach(function () {
    $migrationZone = require base_path('database/migrations/tenant/2025_10_28_145209_create_shipping_zones.php');
    if (! Schema::hasTable('shipping_zones')) {
        $migrationZone->up();
    }

    $migrationRate = require base_path('database/migrations/tenant/2025_10_28_145238_create_shipping_rates.php');
    if (! Schema::hasTable('shipping_rates')) {
        $migrationRate->up();
    }

    $this->repository = new ShippingRepository;
});

test('ShippingRepository creates and retrieves shipping zone with rates', function () {
    $zone = $this->repository->saveZone(ShippingZone::create(
        name: ShippingZoneName::make('Zona Centro'),
        countries: ['MX'],
        states: ['CDMX', 'MEX'],
        priority: 1
    ));

    expect($zone->id())->not->toBeNull()
        ->and($zone->name()->value())->toBe('Zona Centro');

    $rate = $this->repository->saveRate(ShippingRate::create(
        shippingZoneId: $zone->id(),
        name: ShippingRateName::make('Estándar'),
        type: ShippingRateType::flat(),
        cost: ShippingRateCost::fromFloat(8.0)
    ));

    expect($rate->id())->not->toBeNull()
        ->and($rate->name()->value())->toBe('Estándar');

    $foundZone = $this->repository->findZoneById($zone->id());
    expect($foundZone)->not->toBeNull()
        ->and(count($foundZone->rates()))->toBe(1);
});

test('ShippingRepository filters shipping zones and matches location', function () {
    $zone = $this->repository->saveZone(ShippingZone::create(
        name: ShippingZoneName::make('Global Zone'),
        countries: ['US', 'CA']
    ));

    $filter = $this->repository->filterZones(new ShippingZoneFilterCriteria(search: 'Global'));
    expect($filter->total)->toBe(1);

    $matched = $this->repository->findMatchingZones('US');
    expect(count($matched))->toBeGreaterThanOrEqual(1);
});
