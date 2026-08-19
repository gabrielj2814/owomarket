<?php

declare(strict_types=1);

namespace Src\Billing\Application\DTOs;

use Src\Billing\Domain\Entities\Invoice;

final class PaginatedInvoicesResult
{
    /**
     * @param  array<Invoice>  $items
     */
    public function __construct(
        public readonly array $items,
        public readonly int $total,
        public readonly int $currentPage,
        public readonly int $perPage,
        public readonly int $lastPage
    ) {}

    public function toArray(): array
    {
        return [
            'data' => array_map(fn (Invoice $i) => $i->toArray(), $this->items),
            'pagination' => [
                'total' => $this->total,
                'current_page' => $this->currentPage,
                'per_page' => $this->perPage,
                'last_page' => $this->lastPage,
            ],
        ];
    }
}
