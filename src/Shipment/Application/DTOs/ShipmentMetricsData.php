<?php

declare(strict_types=1);

namespace Src\Shipment\Application\DTOs;

final class ShipmentMetricsData
{
    public function __construct(
        public readonly int $totalShipments,
        public readonly int $pendingShipments,
        public readonly int $inTransitShipments,
        public readonly int $deliveredShipments,
        public readonly float $totalShippingCost
    ) {}

    public function toArray(): array
    {
        return [
            'total_shipments' => $this->totalShipments,
            'pending_shipments' => $this->pendingShipments,
            'in_transit_shipments' => $this->inTransitShipments,
            'delivered_shipments' => $this->deliveredShipments,
            'total_shipping_cost' => round($this->totalShippingCost, 2),
        ];
    }
}
