<?php

declare(strict_types=1);

namespace Src\Tax\Application\DTOs;

use Src\Tax\Domain\Entities\TaxRate;

final class PaginatedTaxRatesResult
{
    /**
     * @param  TaxRate[]  $items
     */
    public function __construct(
        public readonly array $items,
        public readonly int $total,
        public readonly int $currentPage,
        public readonly int $perPage,
        public readonly int $lastPage
    ) {}
}
