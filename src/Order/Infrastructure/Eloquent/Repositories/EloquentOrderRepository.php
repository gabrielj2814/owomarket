<?php

declare(strict_types=1);

namespace Src\Order\Infrastructure\Eloquent\Repositories;

use DateTimeImmutable;
use Illuminate\Support\Facades\DB;
use Src\Order\Application\Contracts\Repositories\OrderRepositoryInterface;
use Src\Order\Application\DTOs\FilterOrdersCriteria;
use Src\Order\Application\DTOs\OrderMetricsData;
use Src\Order\Application\DTOs\PaginatedOrderResult;
use Src\Order\Domain\Entities\Order;
use Src\Order\Domain\Entities\OrderItem;
use Src\Order\Domain\ValueObjects\Currency;
use Src\Order\Domain\ValueObjects\Money;
use Src\Order\Domain\ValueObjects\OrderId;
use Src\Order\Domain\ValueObjects\OrderItemId;
use Src\Order\Domain\ValueObjects\OrderNumber;
use Src\Order\Domain\ValueObjects\OrderStatus;
use Src\Order\Domain\ValueObjects\PaymentStatus;
use Src\Order\Infrastructure\Eloquent\Models\Order as EloquentOrder;
use Src\Order\Infrastructure\Eloquent\Models\OrderItem as EloquentOrderItem;

final class EloquentOrderRepository implements OrderRepositoryInterface
{
    public function save(Order $order): void
    {
        DB::transaction(function () use ($order) {
            EloquentOrder::query()->updateOrCreate(
                ['id' => $order->id()->value()],
                [
                    'order_number' => $order->orderNumber()->value(),
                    'customer_id' => $order->customerId(),
                    'status' => $order->status()->value,
                    'subtotal' => $order->subtotal()->amount(),
                    'tax_amount' => $order->taxAmount()->amount(),
                    'shipping_amount' => $order->shippingAmount()->amount(),
                    'discount_amount' => $order->discountAmount()->amount(),
                    'total' => $order->total()->amount(),
                    'currency' => $order->currency()->code(),
                    'payment_method' => $order->paymentMethod(),
                    'payment_status' => $order->paymentStatus()->value,
                    'shipping_method' => $order->shippingMethod(),
                    'notes' => $order->notes(),
                    'customer_note' => $order->customerNote(),
                    'confirmed_at' => $order->confirmedAt()?->format('Y-m-d H:i:s'),
                    'cancelled_at' => $order->cancelledAt()?->format('Y-m-d H:i:s'),
                    'shipped_at' => $order->shippedAt()?->format('Y-m-d H:i:s'),
                    'delivered_at' => $order->deliveredAt()?->format('Y-m-d H:i:s'),
                    'metadata' => $order->metadata(),
                ]
            );

            // Sync items
            $itemIdsToKeep = [];
            foreach ($order->items() as $item) {
                $itemIdsToKeep[] = $item->id()->value();

                EloquentOrderItem::query()->updateOrCreate(
                    ['id' => $item->id()->value()],
                    [
                        'order_id' => $order->id()->value(),
                        'product_id' => $item->productId(),
                        'product_variant_id' => $item->productVariantId(),
                        'product_name' => $item->productName(),
                        'sku' => $item->sku(),
                        'price' => $item->price()->amount(),
                        'quantity' => $item->quantity(),
                        'attributes' => $item->attributes(),
                        'total' => $item->total()->amount(),
                    ]
                );
            }

            // Remove items that are no longer in the order
            EloquentOrderItem::query()
                ->where('order_id', $order->id()->value())
                ->whereNotIn('id', $itemIdsToKeep)
                ->delete();
        });
    }

    public function findById(OrderId $id): ?Order
    {
        $model = EloquentOrder::query()
            ->with(['items'])
            ->find($id->value());

        if (! $model) {
            return null;
        }

        return $this->toDomainEntity($model);
    }

    public function findByOrderNumber(OrderNumber $orderNumber): ?Order
    {
        $model = EloquentOrder::query()
            ->with(['items'])
            ->where('order_number', $orderNumber->value())
            ->first();

        if (! $model) {
            return null;
        }

        return $this->toDomainEntity($model);
    }

    public function filter(FilterOrdersCriteria $criteria): PaginatedOrderResult
    {
        $query = EloquentOrder::query()->with(['items']);

        if ($criteria->search !== null && $criteria->search !== '') {
            $search = $criteria->search;
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                    ->orWhereHas('customer', function ($cq) use ($search) {
                        $cq->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%");
                    });
            });
        }

        if ($criteria->customerId !== null && $criteria->customerId !== '') {
            $query->where('customer_id', $criteria->customerId);
        }

        if ($criteria->status !== null && $criteria->status !== '') {
            $query->where('status', $criteria->status);
        }

        if ($criteria->paymentStatus !== null && $criteria->paymentStatus !== '') {
            $query->where('payment_status', $criteria->paymentStatus);
        }

        if ($criteria->startDate !== null && $criteria->startDate !== '') {
            $query->whereDate('created_at', '>=', $criteria->startDate);
        }

        if ($criteria->endDate !== null && $criteria->endDate !== '') {
            $query->whereDate('created_at', '<=', $criteria->endDate);
        }

        $allowedSorts = ['created_at', 'total', 'order_number', 'status', 'payment_status'];
        $sortBy = in_array($criteria->sortBy, $allowedSorts, true) ? $criteria->sortBy : 'created_at';
        $sortDirection = $criteria->sortDirection === 'asc' ? 'asc' : 'desc';

        $query->orderBy($sortBy, $sortDirection);

        $paginator = $query->paginate(
            perPage: $criteria->perPage,
            page: $criteria->page
        );

        $domainOrders = array_map(
            fn (EloquentOrder $m) => $this->toDomainEntity($m),
            $paginator->items()
        );

        return new PaginatedOrderResult(
            data: $domainOrders,
            total: $paginator->total(),
            currentPage: $paginator->currentPage(),
            perPage: $paginator->perPage(),
            lastPage: $paginator->lastPage()
        );
    }

    public function getMetrics(): OrderMetricsData
    {
        $totalOrders = EloquentOrder::query()->count();
        $pendingOrders = EloquentOrder::query()->where('status', 'pending')->count();
        $processingOrders = EloquentOrder::query()->where('status', 'processing')->count();
        $completedOrders = EloquentOrder::query()->where('status', 'delivered')->count();

        $totalSalesAmount = (float) EloquentOrder::query()
            ->whereNotIn('status', ['cancelled', 'refunded'])
            ->sum('total');

        $averageOrderValue = $totalOrders > 0 ? round($totalSalesAmount / $totalOrders, 2) : 0.0;

        return new OrderMetricsData(
            totalOrders: $totalOrders,
            pendingOrders: $pendingOrders,
            processingOrders: $processingOrders,
            completedOrders: $completedOrders,
            totalSalesAmount: $totalSalesAmount,
            averageOrderValue: $averageOrderValue
        );
    }

    private function toDomainEntity(EloquentOrder $model): Order
    {
        $domainItems = [];
        if ($model->relationLoaded('items') && $model->items) {
            foreach ($model->items as $itemModel) {
                $domainItems[] = new OrderItem(
                    id: new OrderItemId($itemModel->id),
                    orderId: new OrderId($model->id),
                    productId: (string) $itemModel->product_id,
                    productVariantId: $itemModel->product_variant_id ? (string) $itemModel->product_variant_id : null,
                    productName: (string) $itemModel->product_name,
                    sku: (string) $itemModel->sku,
                    price: Money::from((float) $itemModel->price),
                    quantity: (int) $itemModel->quantity,
                    attributes: $itemModel->attributes,
                    total: Money::from((float) $itemModel->total)
                );
            }
        }

        return new Order(
            id: new OrderId($model->id),
            orderNumber: new OrderNumber($model->order_number),
            customerId: (string) $model->customer_id,
            status: OrderStatus::fromString((string) $model->status),
            subtotal: Money::from((float) $model->subtotal),
            taxAmount: Money::from((float) $model->tax_amount),
            shippingAmount: Money::from((float) $model->shipping_amount),
            discountAmount: Money::from((float) $model->discount_amount),
            total: Money::from((float) $model->total),
            currency: new Currency((string) ($model->currency ?? 'USD')),
            paymentMethod: (string) $model->payment_method,
            paymentStatus: PaymentStatus::fromString((string) $model->payment_status),
            shippingMethod: $model->shipping_method,
            notes: $model->notes,
            customerNote: $model->customer_note,
            confirmedAt: $model->confirmed_at ? new DateTimeImmutable($model->confirmed_at->format('Y-m-d H:i:s')) : null,
            cancelledAt: $model->cancelled_at ? new DateTimeImmutable($model->cancelled_at->format('Y-m-d H:i:s')) : null,
            shippedAt: $model->shipped_at ? new DateTimeImmutable($model->shipped_at->format('Y-m-d H:i:s')) : null,
            deliveredAt: $model->delivered_at ? new DateTimeImmutable($model->delivered_at->format('Y-m-d H:i:s')) : null,
            metadata: $model->metadata,
            items: $domainItems,
            createdAt: $model->created_at ? new DateTimeImmutable($model->created_at->format('Y-m-d H:i:s')) : null,
            updatedAt: $model->updated_at ? new DateTimeImmutable($model->updated_at->format('Y-m-d H:i:s')) : null
        );
    }
}
