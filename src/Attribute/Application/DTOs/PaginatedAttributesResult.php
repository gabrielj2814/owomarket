<?php

declare(strict_types=1);

namespace Src\Attribute\Application\DTOs;

use Src\Attribute\Domain\Entities\ProductAttribute;

final class PaginatedAttributesResult
{
    /**
     * @param  ProductAttribute[]  $items
     */
    public function __construct(
        public readonly array $items,
        public readonly int $total,
        public readonly int $currentPage,
        public readonly int $perPage,
        public readonly int $lastPage
    ) {}
}
