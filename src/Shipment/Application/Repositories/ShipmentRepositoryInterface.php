<?php

declare(strict_types=1);

namespace Src\Shipment\Application\Repositories;

use Src\Shipment\Application\DTOs\FilterShipmentsCriteria;
use Src\Shipment\Application\DTOs\PaginatedShipmentResult;
use Src\Shipment\Application\DTOs\ShipmentMetricsData;
use Src\Shipment\Domain\Entities\Shipment;
use Src\Shipment\Domain\ValueObjects\ShipmentId;
use Src\Shipment\Domain\ValueObjects\TrackingNumber;

interface ShipmentRepositoryInterface
{
    public function save(Shipment $shipment): Shipment;

    public function findById(ShipmentId $id): ?Shipment;

    /**
     * @return Shipment[]
     */
    public function findByOrderId(string $orderId): array;

    public function findByTrackingNumber(TrackingNumber $trackingNumber): ?Shipment;

    public function filter(FilterShipmentsCriteria $criteria): PaginatedShipmentResult;

    public function getMetrics(): ShipmentMetricsData;

    public function delete(ShipmentId $id): bool;
}
