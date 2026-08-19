<?php

declare(strict_types=1);

namespace Src\Order\Application\DTOs;

use Src\Order\Domain\Entities\Order;

final class PaginatedOrderResult
{
    /**
     * @param  Order[]  $data
     */
    public function __construct(
        public readonly array $data,
        public readonly int $total,
        public readonly int $currentPage,
        public readonly int $perPage,
        public readonly int $lastPage
    ) {}

    public function toArray(): array
    {
        return [
            'data' => array_map(fn (Order $order) => $order->toArray(), $this->data),
            'pagination' => [
                'total' => $this->total,
                'current_page' => $this->currentPage,
                'per_page' => $this->perPage,
                'last_page' => $this->lastPage,
            ],
        ];
    }
}
