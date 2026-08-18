<?php

declare(strict_types=1);

namespace Src\Order\Application\DTOs;

final class OrderMetricsData
{
    public function __construct(
        public readonly int $totalOrders,
        public readonly int $pendingOrders,
        public readonly int $processingOrders,
        public readonly int $completedOrders,
        public readonly float $totalSalesAmount,
        public readonly float $averageOrderValue
    ) {}

    public function toArray(): array
    {
        return [
            'total_orders' => $this->totalOrders,
            'pending_orders' => $this->pendingOrders,
            'processing_orders' => $this->processingOrders,
            'completed_orders' => $this->completedOrders,
            'total_sales_amount' => $this->totalSalesAmount,
            'average_order_value' => $this->averageOrderValue,
        ];
    }
}
