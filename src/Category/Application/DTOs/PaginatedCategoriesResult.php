<?php

declare(strict_types=1);

namespace Src\Category\Application\DTOs;

use Src\Category\Domain\Entities\Category;

final class PaginatedCategoriesResult
{
    /**
     * @param  Category[]  $items
     */
    public function __construct(
        public readonly array $items,
        public readonly int $total,
        public readonly int $currentPage,
        public readonly int $perPage,
        public readonly int $lastPage
    ) {}
}
