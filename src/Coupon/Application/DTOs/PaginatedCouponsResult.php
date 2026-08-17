<?php

declare(strict_types=1);

namespace Src\Coupon\Application\DTOs;

use Src\Coupon\Domain\Entities\Coupon;

final class PaginatedCouponsResult
{
    /**
     * @param  Coupon[]  $items
     */
    public function __construct(
        public readonly array $items,
        public readonly int $total,
        public readonly int $currentPage,
        public readonly int $perPage,
        public readonly int $lastPage
    ) {}
}
