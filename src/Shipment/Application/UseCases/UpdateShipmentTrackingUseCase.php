<?php

declare(strict_types=1);

namespace Src\Shipment\Application\UseCases;

use DateTimeImmutable;
use Src\Shipment\Application\DTOs\UpdateTrackingData;
use Src\Shipment\Application\Repositories\ShipmentRepositoryInterface;
use Src\Shipment\Domain\Entities\Shipment;
use Src\Shipment\Domain\Exceptions\ShipmentNotFoundException;
use Src\Shipment\Domain\ValueObjects\Carrier;
use Src\Shipment\Domain\ValueObjects\ShipmentCost;
use Src\Shipment\Domain\ValueObjects\ShipmentId;
use Src\Shipment\Domain\ValueObjects\ShipmentServiceType;
use Src\Shipment\Domain\ValueObjects\TrackingNumber;

final class UpdateShipmentTrackingUseCase
{
    public function __construct(
        private readonly ShipmentRepositoryInterface $repository
    ) {}

    public function execute(string $shipmentId, UpdateTrackingData $data): Shipment
    {
        $id = ShipmentId::fromString($shipmentId);
        $shipment = $this->repository->findById($id);

        if ($shipment === null) {
            throw ShipmentNotFoundException::forId($shipmentId);
        }

        $shippedAt = ! empty($data->shippedAt) ? new DateTimeImmutable($data->shippedAt) : null;
        $estimatedDelivery = ! empty($data->estimatedDelivery) ? new DateTimeImmutable($data->estimatedDelivery) : null;

        $shipment->assignTrackingNumber(
            trackingNumber: new TrackingNumber($data->trackingNumber),
            shippedAt: $shippedAt,
            estimatedDelivery: $estimatedDelivery
        );

        if (! empty($data->carrier) && ! empty($data->service)) {
            $cost = $data->cost !== null ? new ShipmentCost($data->cost) : null;
            $shipment->updateCarrierAndService(
                carrier: new Carrier($data->carrier),
                service: new ShipmentServiceType($data->service),
                cost: $cost
            );
        }

        if ($data->notes !== null) {
            $shipment->updateNotes($data->notes);
        }

        return $this->repository->save($shipment);
    }
}
