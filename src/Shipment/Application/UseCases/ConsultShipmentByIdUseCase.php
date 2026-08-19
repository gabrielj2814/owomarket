<?php

declare(strict_types=1);

namespace Src\Shipment\Application\UseCases;

use Src\Shipment\Application\Repositories\ShipmentRepositoryInterface;
use Src\Shipment\Domain\Entities\Shipment;
use Src\Shipment\Domain\Exceptions\ShipmentNotFoundException;
use Src\Shipment\Domain\ValueObjects\ShipmentId;

final class ConsultShipmentByIdUseCase
{
    public function __construct(
        private readonly ShipmentRepositoryInterface $repository
    ) {}

    public function execute(string $id): Shipment
    {
        $shipmentId = ShipmentId::fromString($id);
        $shipment = $this->repository->findById($shipmentId);

        if ($shipment === null) {
            throw ShipmentNotFoundException::forId($id);
        }

        return $shipment;
    }
}
