<?php

declare(strict_types=1);

namespace Src\Shipment\Application\UseCases;

use DateTimeImmutable;
use Src\Shipment\Application\Repositories\ShipmentRepositoryInterface;
use Src\Shipment\Domain\Entities\Shipment;
use Src\Shipment\Domain\Exceptions\ShipmentNotFoundException;
use Src\Shipment\Domain\ValueObjects\ShipmentId;

final class MarkShipmentAsDeliveredUseCase
{
    public function __construct(
        private readonly ShipmentRepositoryInterface $repository
    ) {}

    public function execute(string $shipmentId, ?string $deliveredAt = null): Shipment
    {
        $id = ShipmentId::fromString($shipmentId);
        $shipment = $this->repository->findById($id);

        if ($shipment === null) {
            throw ShipmentNotFoundException::forId($shipmentId);
        }

        $deliveredAtDt = ! empty($deliveredAt) ? new DateTimeImmutable($deliveredAt) : null;
        $shipment->markAsDelivered($deliveredAtDt);

        return $this->repository->save($shipment);
    }
}
