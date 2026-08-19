<?php

declare(strict_types=1);

namespace Src\Shipping\Application\UseCase;

use Src\Shipping\Application\Contracts\ShippingRepositoryInterface;
use Src\Shipping\Domain\Entities\ShippingZone;
use Src\Shipping\Domain\Exceptions\ShippingZoneNotFoundException;
use Src\Shipping\Domain\ValueObjects\ShippingZoneId;

final class ConsultShippingZoneByIdUseCase
{
    public function __construct(
        private readonly ShippingRepositoryInterface $repository
    ) {}

    public function execute(string $id): ShippingZone
    {
        $zone = $this->repository->findZoneById(ShippingZoneId::fromString($id));

        if ($zone === null) {
            throw new ShippingZoneNotFoundException($id);
        }

        return $zone;
    }
}
