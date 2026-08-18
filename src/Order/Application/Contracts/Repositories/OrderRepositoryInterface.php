<?php

declare(strict_types=1);

namespace Src\Order\Application\Contracts\Repositories;

use Src\Order\Application\DTOs\FilterOrdersCriteria;
use Src\Order\Application\DTOs\OrderMetricsData;
use Src\Order\Application\DTOs\PaginatedOrderResult;
use Src\Order\Domain\Entities\Order;
use Src\Order\Domain\ValueObjects\OrderId;
use Src\Order\Domain\ValueObjects\OrderNumber;

interface OrderRepositoryInterface
{
    public function save(Order $order): void;

    public function findById(OrderId $id): ?Order;

    public function findByOrderNumber(OrderNumber $orderNumber): ?Order;

    public function filter(FilterOrdersCriteria $criteria): PaginatedOrderResult;

    public function getMetrics(): OrderMetricsData;
}
