<?php

declare(strict_types=1);

namespace Src\Shipping\Application\UseCase;

use Src\Shipping\Application\Contracts\ShippingRepositoryInterface;
use Src\Shipping\Domain\Exceptions\ShippingZoneNotFoundException;
use Src\Shipping\Domain\ValueObjects\ShippingZoneId;

final class DeleteShippingZoneUseCase
{
    public function __construct(
        private readonly ShippingRepositoryInterface $repository
    ) {}

    public function execute(string $id): void
    {
        $zoneId = ShippingZoneId::fromString($id);
        $zone = $this->repository->findZoneById($zoneId);

        if ($zone === null) {
            throw new ShippingZoneNotFoundException($id);
        }

        $this->repository->deleteZone($zoneId);
    }
}
