<?php

declare(strict_types=1);

namespace Src\Shipment\Application\DTOs;

final class CreateShipmentData
{
    public function __construct(
        public readonly string $orderId,
        public readonly string $carrier,
        public readonly string $service,
        public readonly float $cost = 0.0,
        public readonly ?string $trackingNumber = null,
        public readonly ?string $notes = null,
        public readonly ?string $estimatedDelivery = null,
        public readonly ?array $metadata = null,
        public readonly ?string $id = null
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            orderId: $data['order_id'],
            carrier: $data['carrier'],
            service: $data['service'],
            cost: isset($data['cost']) ? (float) $data['cost'] : 0.0,
            trackingNumber: $data['tracking_number'] ?? null,
            notes: $data['notes'] ?? null,
            estimatedDelivery: $data['estimated_delivery'] ?? null,
            metadata: $data['metadata'] ?? null,
            id: $data['id'] ?? null
        );
    }
}
