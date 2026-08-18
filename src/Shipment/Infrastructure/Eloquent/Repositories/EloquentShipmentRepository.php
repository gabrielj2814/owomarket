<?php

declare(strict_types=1);

namespace Src\Shipment\Infrastructure\Eloquent\Repositories;

use Illuminate\Support\Facades\DB;
use Src\Order\Infrastructure\Eloquent\Models\Order as EloquentOrder;
use Src\Shipment\Application\DTOs\FilterShipmentsCriteria;
use Src\Shipment\Application\DTOs\PaginatedShipmentResult;
use Src\Shipment\Application\DTOs\ShipmentMetricsData;
use Src\Shipment\Application\Repositories\ShipmentRepositoryInterface;
use Src\Shipment\Domain\Entities\Shipment;
use Src\Shipment\Domain\ValueObjects\ShipmentId;
use Src\Shipment\Domain\ValueObjects\TrackingNumber;
use Src\Shipment\Infrastructure\Eloquent\Models\Shipment as EloquentShipment;

final class EloquentShipmentRepository implements ShipmentRepositoryInterface
{
    public function save(Shipment $shipment): Shipment
    {
        return DB::transaction(function () use ($shipment) {
            $eloquent = EloquentShipment::updateOrCreate(
                ['id' => $shipment->id()->value()],
                [
                    'order_id' => $shipment->orderId(),
                    'tracking_number' => $shipment->trackingNumber()?->value(),
                    'carrier' => $shipment->carrier()->value(),
                    'service' => $shipment->service()->value(),
                    'cost' => $shipment->cost()->amount(),
                    'notes' => $shipment->notes(),
                    'shipped_at' => $shipment->shippedAt()?->format('Y-m-d H:i:s'),
                    'estimated_delivery' => $shipment->estimatedDelivery()?->format('Y-m-d H:i:s'),
                    'delivered_at' => $shipment->deliveredAt()?->format('Y-m-d H:i:s'),
                    'metadata' => $shipment->metadata(),
                ]
            );

            // Sync status with related Order
            $order = EloquentOrder::find($shipment->orderId());
            if ($order !== null) {
                if ($shipment->isDelivered()) {
                    $order->update([
                        'status' => 'delivered',
                        'delivered_at' => $shipment->deliveredAt()?->format('Y-m-d H:i:s') ?? now(),
                    ]);
                } elseif ($shipment->isInTransit() && ! in_array($order->status, ['delivered', 'cancelled', 'refunded'], true)) {
                    $order->update([
                        'status' => 'shipped',
                        'shipped_at' => $shipment->shippedAt()?->format('Y-m-d H:i:s') ?? now(),
                        'shipping_method' => $shipment->carrier()->value(),
                    ]);
                }
            }

            return $eloquent->fresh()->toDomain();
        });
    }

    public function findById(ShipmentId $id): ?Shipment
    {
        $eloquent = EloquentShipment::find($id->value());

        return $eloquent?->toDomain();
    }

    /**
     * @return Shipment[]
     */
    public function findByOrderId(string $orderId): array
    {
        return EloquentShipment::where('order_id', $orderId)
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(fn (EloquentShipment $s) => $s->toDomain())
            ->all();
    }

    public function findByTrackingNumber(TrackingNumber $trackingNumber): ?Shipment
    {
        $eloquent = EloquentShipment::where('tracking_number', $trackingNumber->value())->first();

        return $eloquent?->toDomain();
    }

    public function filter(FilterShipmentsCriteria $criteria): PaginatedShipmentResult
    {
        $query = EloquentShipment::query();

        if (! empty($criteria->search)) {
            $term = trim($criteria->search);
            $query->where(function ($q) use ($term) {
                $q->where('tracking_number', 'like', "%{$term}%")
                    ->orWhere('carrier', 'like', "%{$term}%")
                    ->orWhere('service', 'like', "%{$term}%")
                    ->orWhere('notes', 'like', "%{$term}%")
                    ->orWhereHas('order', function ($oq) use ($term) {
                        $oq->where('order_number', 'like', "%{$term}%")
                            ->orWhereHas('customer', function ($cq) use ($term) {
                                $cq->where('name', 'like', "%{$term}%")
                                    ->orWhere('email', 'like', "%{$term}%");
                            });
                    });
            });
        }

        if (! empty($criteria->carrier)) {
            $query->where('carrier', 'like', "%{$criteria->carrier}%");
        }

        if (! empty($criteria->orderId)) {
            $query->where('order_id', $criteria->orderId);
        }

        if (! empty($criteria->status)) {
            $status = strtolower($criteria->status);
            if ($status === 'delivered') {
                $query->whereNotNull('delivered_at');
            } elseif ($status === 'in_transit') {
                $query->whereNull('delivered_at')
                    ->where(function ($q) {
                        $q->whereNotNull('shipped_at')
                            ->orWhereNotNull('tracking_number');
                    });
            } elseif ($status === 'pending') {
                $query->whereNull('delivered_at')
                    ->whereNull('shipped_at')
                    ->whereNull('tracking_number');
            }
        }

        if (! empty($criteria->dateFrom)) {
            $query->whereDate('created_at', '>=', $criteria->dateFrom);
        }

        if (! empty($criteria->dateTo)) {
            $query->whereDate('created_at', '<=', $criteria->dateTo);
        }

        $allowedSorts = ['created_at', 'shipped_at', 'delivered_at', 'carrier', 'cost'];
        $sortBy = in_array($criteria->sortBy, $allowedSorts, true) ? $criteria->sortBy : 'created_at';
        $direction = strtolower($criteria->sortDirection) === 'asc' ? 'asc' : 'desc';

        $query->orderBy($sortBy, $direction);

        $paginator = $query->paginate(
            perPage: $criteria->perPage,
            page: $criteria->page
        );

        $items = array_map(fn (EloquentShipment $s) => $s->toDomain(), $paginator->items());

        return new PaginatedShipmentResult(
            items: $items,
            total: $paginator->total(),
            perPage: $paginator->perPage(),
            currentPage: $paginator->currentPage(),
            lastPage: $paginator->lastPage()
        );
    }

    public function getMetrics(): ShipmentMetricsData
    {
        $total = EloquentShipment::count();
        $delivered = EloquentShipment::whereNotNull('delivered_at')->count();
        $inTransit = EloquentShipment::whereNull('delivered_at')
            ->where(function ($q) {
                $q->whereNotNull('shipped_at')
                    ->orWhereNotNull('tracking_number');
            })->count();
        $pending = EloquentShipment::whereNull('delivered_at')
            ->whereNull('shipped_at')
            ->whereNull('tracking_number')
            ->count();
        $totalCost = (float) EloquentShipment::sum('cost');

        return new ShipmentMetricsData(
            totalShipments: $total,
            pendingShipments: $pending,
            inTransitShipments: $inTransit,
            deliveredShipments: $delivered,
            totalShippingCost: $totalCost
        );
    }

    public function delete(ShipmentId $id): bool
    {
        $deleted = EloquentShipment::destroy($id->value());

        return $deleted > 0;
    }
}
