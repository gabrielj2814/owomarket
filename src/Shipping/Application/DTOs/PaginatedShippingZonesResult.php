<?php

declare(strict_types=1);

namespace Src\Shipping\Application\DTOs;

use Src\Shipping\Domain\Entities\ShippingZone;

final class PaginatedShippingZonesResult
{
    /**
     * @param  ShippingZone[]  $items
     */
    public function __construct(
        public readonly array $items,
        public readonly int $total,
        public readonly int $currentPage,
        public readonly int $perPage,
        public readonly int $lastPage
    ) {}
}
