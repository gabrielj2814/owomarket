<?php

declare(strict_types=1);

namespace Src\Brand\Application\DTOs;

use Src\Brand\Domain\Entities\Brand;

final class PaginatedBrandsResult
{
    /**
     * @param  Brand[]  $items
     */
    public function __construct(
        public readonly array $items,
        public readonly int $total,
        public readonly int $currentPage,
        public readonly int $perPage,
        public readonly int $lastPage
    ) {}
}
