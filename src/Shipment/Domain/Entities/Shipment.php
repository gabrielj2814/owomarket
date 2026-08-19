<?php

declare(strict_types=1);

namespace Src\Shipment\Domain\Entities;

use DateTimeImmutable;
use Src\Shipment\Domain\Exceptions\ShipmentAlreadyDeliveredException;
use Src\Shipment\Domain\ValueObjects\Carrier;
use Src\Shipment\Domain\ValueObjects\ShipmentCost;
use Src\Shipment\Domain\ValueObjects\ShipmentId;
use Src\Shipment\Domain\ValueObjects\ShipmentServiceType;
use Src\Shipment\Domain\ValueObjects\ShipmentStatus;
use Src\Shipment\Domain\ValueObjects\TrackingNumber;

final class Shipment
{
    public function __construct(
        private readonly ShipmentId $id,
        private readonly string $orderId,
        private Carrier $carrier,
        private ShipmentServiceType $service,
        private ShipmentCost $cost,
        private ?TrackingNumber $trackingNumber = null,
        private ?string $notes = null,
        private ?DateTimeImmutable $shippedAt = null,
        private ?DateTimeImmutable $estimatedDelivery = null,
        private ?DateTimeImmutable $deliveredAt = null,
        private ?array $metadata = null,
        private readonly ?DateTimeImmutable $createdAt = null,
        private ?DateTimeImmutable $updatedAt = null
    ) {}

    public static function create(
        string $orderId,
        string|Carrier $carrier,
        string|ShipmentServiceType $service,
        float|ShipmentCost $cost = 0.0,
        string|TrackingNumber|null $trackingNumber = null,
        ?string $notes = null,
        ?DateTimeImmutable $estimatedDelivery = null,
        ?array $metadata = null,
        ?ShipmentId $id = null
    ): self {
        $carrierVo = $carrier instanceof Carrier ? $carrier : new Carrier($carrier);
        $serviceVo = $service instanceof ShipmentServiceType ? $service : new ShipmentServiceType($service);
        $costVo = $cost instanceof ShipmentCost ? $cost : new ShipmentCost($cost);
        $trackingVo = null;

        if ($trackingNumber !== null) {
            $trackingVo = $trackingNumber instanceof TrackingNumber
                ? $trackingNumber
                : new TrackingNumber($trackingNumber);
        }

        $now = new DateTimeImmutable;

        return new self(
            id: $id ?? ShipmentId::random(),
            orderId: $orderId,
            carrier: $carrierVo,
            service: $serviceVo,
            cost: $costVo,
            trackingNumber: $trackingVo,
            notes: $notes,
            shippedAt: $trackingVo !== null ? $now : null,
            estimatedDelivery: $estimatedDelivery,
            deliveredAt: null,
            metadata: $metadata,
            createdAt: $now,
            updatedAt: $now
        );
    }

    public function id(): ShipmentId
    {
        return $this->id;
    }

    public function orderId(): string
    {
        return $this->orderId;
    }

    public function carrier(): Carrier
    {
        return $this->carrier;
    }

    public function service(): ShipmentServiceType
    {
        return $this->service;
    }

    public function cost(): ShipmentCost
    {
        return $this->cost;
    }

    public function trackingNumber(): ?TrackingNumber
    {
        return $this->trackingNumber;
    }

    public function notes(): ?string
    {
        return $this->notes;
    }

    public function shippedAt(): ?DateTimeImmutable
    {
        return $this->shippedAt;
    }

    public function estimatedDelivery(): ?DateTimeImmutable
    {
        return $this->estimatedDelivery;
    }

    public function deliveredAt(): ?DateTimeImmutable
    {
        return $this->deliveredAt;
    }

    public function metadata(): ?array
    {
        return $this->metadata;
    }

    public function createdAt(): ?DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function updatedAt(): ?DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function status(): ShipmentStatus
    {
        if ($this->deliveredAt !== null) {
            return ShipmentStatus::DELIVERED;
        }

        if ($this->shippedAt !== null || $this->trackingNumber !== null) {
            return ShipmentStatus::IN_TRANSIT;
        }

        return ShipmentStatus::PENDING;
    }

    public function isPending(): bool
    {
        return $this->status() === ShipmentStatus::PENDING;
    }

    public function isInTransit(): bool
    {
        return $this->status() === ShipmentStatus::IN_TRANSIT;
    }

    public function isDelivered(): bool
    {
        return $this->status() === ShipmentStatus::DELIVERED;
    }

    public function assignTrackingNumber(
        string|TrackingNumber $trackingNumber,
        ?DateTimeImmutable $shippedAt = null,
        ?DateTimeImmutable $estimatedDelivery = null
    ): void {
        if ($this->isDelivered()) {
            throw ShipmentAlreadyDeliveredException::forId($this->id->value());
        }

        $this->trackingNumber = $trackingNumber instanceof TrackingNumber
            ? $trackingNumber
            : new TrackingNumber($trackingNumber);

        $this->shippedAt = $shippedAt ?? $this->shippedAt ?? new DateTimeImmutable;
        if ($estimatedDelivery !== null) {
            $this->estimatedDelivery = $estimatedDelivery;
        }

        $this->updatedAt = new DateTimeImmutable;
    }

    public function markAsDelivered(?DateTimeImmutable $deliveredAt = null): void
    {
        if ($this->isDelivered()) {
            return;
        }

        $now = new DateTimeImmutable;
        if ($this->shippedAt === null) {
            $this->shippedAt = $now;
        }

        $this->deliveredAt = $deliveredAt ?? $now;
        $this->updatedAt = $now;
    }

    public function updateCarrierAndService(
        string|Carrier $carrier,
        string|ShipmentServiceType $service,
        float|ShipmentCost|null $cost = null
    ): void {
        if ($this->isDelivered()) {
            throw ShipmentAlreadyDeliveredException::forId($this->id->value());
        }

        $this->carrier = $carrier instanceof Carrier ? $carrier : new Carrier($carrier);
        $this->service = $service instanceof ShipmentServiceType ? $service : new ShipmentServiceType($service);

        if ($cost !== null) {
            $this->cost = $cost instanceof ShipmentCost ? $cost : new ShipmentCost($cost);
        }

        $this->updatedAt = new DateTimeImmutable;
    }

    public function updateNotes(?string $notes): void
    {
        $this->notes = $notes;
        $this->updatedAt = new DateTimeImmutable;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id->value(),
            'order_id' => $this->orderId,
            'tracking_number' => $this->trackingNumber?->value(),
            'carrier' => $this->carrier->value(),
            'service' => $this->service->value(),
            'cost' => $this->cost->amount(),
            'notes' => $this->notes,
            'status' => $this->status()->value,
            'shipped_at' => $this->shippedAt?->format('Y-m-d H:i:s'),
            'estimated_delivery' => $this->estimatedDelivery?->format('Y-m-d H:i:s'),
            'delivered_at' => $this->deliveredAt?->format('Y-m-d H:i:s'),
            'metadata' => $this->metadata,
            'created_at' => $this->createdAt?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updatedAt?->format('Y-m-d H:i:s'),
        ];
    }
}
