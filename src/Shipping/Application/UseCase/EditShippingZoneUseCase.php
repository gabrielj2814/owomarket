<?php

declare(strict_types=1);

namespace Src\Shipping\Application\UseCase;

use Src\Shipping\Application\Contracts\ShippingRepositoryInterface;
use Src\Shipping\Domain\Entities\ShippingZone;
use Src\Shipping\Domain\Exceptions\ShippingZoneNotFoundException;
use Src\Shipping\Domain\ValueObjects\ShippingZoneId;
use Src\Shipping\Domain\ValueObjects\ShippingZoneName;

final class EditShippingZoneUseCase
{
    public function __construct(
        private readonly ShippingRepositoryInterface $repository
    ) {}

    public function execute(
        string $id,
        string $name,
        ?array $countries = null,
        ?array $states = null,
        ?array $postalCodes = null,
        int $priority = 0,
        bool $isActive = true
    ): ShippingZone {
        $zoneId = ShippingZoneId::fromString($id);
        $zone = $this->repository->findZoneById($zoneId);

        if ($zone === null) {
            throw new ShippingZoneNotFoundException($id);
        }

        $zone->changeName(ShippingZoneName::make($name));
        $zone->changeLocations($countries, $states, $postalCodes);
        $zone->changePriority($priority);

        if ($isActive) {
            $zone->activate();
        } else {
            $zone->deactivate();
        }

        return $this->repository->updateZone($zone);
    }
}
