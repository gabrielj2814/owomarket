<?php

declare(strict_types=1);

namespace Src\Shipment\Application\DTOs;

use Src\Shipment\Domain\Entities\Shipment;

final class PaginatedShipmentResult
{
    /**
     * @param  Shipment[]  $items
     */
    public function __construct(
        public readonly array $items,
        public readonly int $total,
        public readonly int $perPage,
        public readonly int $currentPage,
        public readonly int $lastPage
    ) {}

    public function toArray(): array
    {
        return [
            'data' => array_map(fn (Shipment $s) => $s->toArray(), $this->items),
            'total' => $this->total,
            'per_page' => $this->perPage,
            'current_page' => $this->currentPage,
            'last_page' => $this->lastPage,
        ];
    }
}
