<?php

declare(strict_types=1);

namespace Src\Shipment\Application\DTOs;

final class UpdateTrackingData
{
    public function __construct(
        public readonly string $trackingNumber,
        public readonly ?string $carrier = null,
        public readonly ?string $service = null,
        public readonly ?float $cost = null,
        public readonly ?string $shippedAt = null,
        public readonly ?string $estimatedDelivery = null,
        public readonly ?string $notes = null
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            trackingNumber: $data['tracking_number'],
            carrier: $data['carrier'] ?? null,
            service: $data['service'] ?? null,
            cost: isset($data['cost']) ? (float) $data['cost'] : null,
            shippedAt: $data['shipped_at'] ?? null,
            estimatedDelivery: $data['estimated_delivery'] ?? null,
            notes: $data['notes'] ?? null
        );
    }
}
