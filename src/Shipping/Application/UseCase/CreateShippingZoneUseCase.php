<?php

declare(strict_types=1);

namespace Src\Shipping\Application\UseCase;

use Src\Shipping\Application\Contracts\ShippingRepositoryInterface;
use Src\Shipping\Domain\Entities\ShippingZone;
use Src\Shipping\Domain\ValueObjects\ShippingStatus;
use Src\Shipping\Domain\ValueObjects\ShippingZoneName;

final class CreateShippingZoneUseCase
{
    public function __construct(
        private readonly ShippingRepositoryInterface $repository
    ) {}

    public function execute(
        string $name,
        ?array $countries = null,
        ?array $states = null,
        ?array $postalCodes = null,
        int $priority = 0,
        bool $isActive = true
    ): ShippingZone {
        $zone = ShippingZone::create(
            name: ShippingZoneName::make($name),
            countries: $countries,
            states: $states,
            postalCodes: $postalCodes,
            priority: $priority,
            isActive: ShippingStatus::fromBool($isActive)
        );

        return $this->repository->saveZone($zone);
    }
}
