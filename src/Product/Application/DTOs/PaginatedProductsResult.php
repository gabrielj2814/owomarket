<?php

declare(strict_types=1);

namespace Src\Product\Application\DTOs;

use Src\Product\Domain\Entities\Product;

final class PaginatedProductsResult
{
    /**
     * @param  Product[]  $items
     */
    public function __construct(
        public readonly array $items,
        public readonly int $total,
        public readonly int $currentPage,
        public readonly int $perPage,
        public readonly int $lastPage
    ) {}
}
