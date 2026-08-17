<?php

declare(strict_types=1);

namespace Src\Shipping\Application\Contracts;

use Src\Shipping\Application\DTOs\PaginatedShippingZonesResult;
use Src\Shipping\Application\DTOs\ShippingZoneFilterCriteria;
use Src\Shipping\Domain\Entities\ShippingRate;
use Src\Shipping\Domain\Entities\ShippingZone;
use Src\Shipping\Domain\ValueObjects\ShippingRateId;
use Src\Shipping\Domain\ValueObjects\ShippingZoneId;

interface ShippingRepositoryInterface
{
    public function saveZone(ShippingZone $zone): ShippingZone;

    public function findZoneById(ShippingZoneId $id): ?ShippingZone;

    public function updateZone(ShippingZone $zone): ShippingZone;

    public function deleteZone(ShippingZoneId $id): void;

    public function filterZones(ShippingZoneFilterCriteria $criteria): PaginatedShippingZonesResult;

    /**
     * @return ShippingZone[]
     */
    public function findMatchingZones(?string $country = null, ?string $state = null, ?string $postalCode = null): array;

    public function saveRate(ShippingRate $rate): ShippingRate;

    public function findRateById(ShippingRateId $id): ?ShippingRate;

    public function deleteRate(ShippingRateId $id): void;
}
