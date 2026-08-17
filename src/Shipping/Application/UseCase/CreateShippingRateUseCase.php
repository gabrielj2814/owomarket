<?php

declare(strict_types=1);

namespace Src\Shipping\Application\UseCase;

use Src\Shipping\Application\Contracts\ShippingRepositoryInterface;
use Src\Shipping\Domain\Entities\ShippingRate;
use Src\Shipping\Domain\Exceptions\ShippingZoneNotFoundException;
use Src\Shipping\Domain\ValueObjects\ShippingRateCost;
use Src\Shipping\Domain\ValueObjects\ShippingRateName;
use Src\Shipping\Domain\ValueObjects\ShippingRateType;
use Src\Shipping\Domain\ValueObjects\ShippingStatus;
use Src\Shipping\Domain\ValueObjects\ShippingZoneId;

final class CreateShippingRateUseCase
{
    public function __construct(
        private readonly ShippingRepositoryInterface $repository
    ) {}

    public function execute(
        string $shippingZoneId,
        string $name,
        string $type,
        float $cost,
        ?float $minValue = null,
        ?float $maxValue = null,
        bool $isActive = true
    ): ShippingRate {
        $zoneId = ShippingZoneId::fromString($shippingZoneId);
        $zone = $this->repository->findZoneById($zoneId);

        if ($zone === null) {
            throw new ShippingZoneNotFoundException($shippingZoneId);
        }

        $rate = ShippingRate::create(
            shippingZoneId: $zoneId,
            name: ShippingRateName::make($name),
            type: ShippingRateType::fromString($type),
            cost: ShippingRateCost::fromFloat($cost),
            minValue: $minValue,
            maxValue: $maxValue,
            isActive: ShippingStatus::fromBool($isActive)
        );

        return $this->repository->saveRate($rate);
    }
}
