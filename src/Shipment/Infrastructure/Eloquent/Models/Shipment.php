<?php

declare(strict_types=1);

namespace Src\Shipment\Infrastructure\Eloquent\Models;

use DateTimeImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Src\Order\Infrastructure\Eloquent\Models\Order;
use Src\Shipment\Domain\Entities\Shipment as DomainShipment;
use Src\Shipment\Domain\ValueObjects\Carrier;
use Src\Shipment\Domain\ValueObjects\ShipmentCost;
use Src\Shipment\Domain\ValueObjects\ShipmentId;
use Src\Shipment\Domain\ValueObjects\ShipmentServiceType;
use Src\Shipment\Domain\ValueObjects\TrackingNumber;

class Shipment extends Model
{
    use HasUuids;

    protected $table = 'shipments';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'order_id',
        'tracking_number',
        'carrier',
        'service',
        'cost',
        'notes',
        'shipped_at',
        'estimated_delivery',
        'delivered_at',
        'metadata',
    ];

    protected $casts = [
        'cost' => 'float',
        'metadata' => 'array',
        'shipped_at' => 'datetime',
        'estimated_delivery' => 'datetime',
        'delivered_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id', 'id');
    }

    public function toDomain(): DomainShipment
    {
        $trackingVo = ! empty($this->tracking_number)
            ? new TrackingNumber($this->tracking_number)
            : null;

        $shippedAtDt = $this->shipped_at
            ? DateTimeImmutable::createFromMutable($this->shipped_at->toDateTime())
            : null;

        $estimatedDeliveryDt = $this->estimated_delivery
            ? DateTimeImmutable::createFromMutable($this->estimated_delivery->toDateTime())
            : null;

        $deliveredAtDt = $this->delivered_at
            ? DateTimeImmutable::createFromMutable($this->delivered_at->toDateTime())
            : null;

        $createdAtDt = $this->created_at
            ? DateTimeImmutable::createFromMutable($this->created_at->toDateTime())
            : null;

        $updatedAtDt = $this->updated_at
            ? DateTimeImmutable::createFromMutable($this->updated_at->toDateTime())
            : null;

        return new DomainShipment(
            id: ShipmentId::fromString($this->id),
            orderId: $this->order_id,
            carrier: new Carrier($this->carrier),
            service: new ShipmentServiceType($this->service),
            cost: new ShipmentCost((float) $this->cost),
            trackingNumber: $trackingVo,
            notes: $this->notes,
            shippedAt: $shippedAtDt,
            estimatedDelivery: $estimatedDeliveryDt,
            deliveredAt: $deliveredAtDt,
            metadata: $this->metadata,
            createdAt: $createdAtDt,
            updatedAt: $updatedAtDt
        );
    }
}
