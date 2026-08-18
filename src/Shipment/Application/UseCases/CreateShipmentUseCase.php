<?php

declare(strict_types=1);

namespace Src\Shipment\Application\UseCases;

use DateTimeImmutable;
use Src\Shipment\Application\DTOs\CreateShipmentData;
use Src\Shipment\Application\Repositories\ShipmentRepositoryInterface;
use Src\Shipment\Domain\Entities\Shipment;
use Src\Shipment\Domain\ValueObjects\Carrier;
use Src\Shipment\Domain\ValueObjects\ShipmentCost;
use Src\Shipment\Domain\ValueObjects\ShipmentId;
use Src\Shipment\Domain\ValueObjects\ShipmentServiceType;
use Src\Shipment\Domain\ValueObjects\TrackingNumber;

final class CreateShipmentUseCase
{
    public function __construct(
        private readonly ShipmentRepositoryInterface $repository
    ) {}

    public function execute(CreateShipmentData $data): Shipment
    {
        $estimatedDelivery = null;
        if (! empty($data->estimatedDelivery)) {
            $estimatedDelivery = new DateTimeImmutable($data->estimatedDelivery);
        }

        $shipment = Shipment::create(
            orderId: $data->orderId,
            carrier: new Carrier($data->carrier),
            service: new ShipmentServiceType($data->service),
            cost: new ShipmentCost($data->cost),
            trackingNumber: ! empty($data->trackingNumber) ? new TrackingNumber($data->trackingNumber) : null,
            notes: $data->notes,
            estimatedDelivery: $estimatedDelivery,
            metadata: $data->metadata,
            id: ! empty($data->id) ? ShipmentId::fromString($data->id) : null
        );

        return $this->repository->save($shipment);
    }
}
