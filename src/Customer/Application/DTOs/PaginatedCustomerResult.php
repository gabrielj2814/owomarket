<?php

declare(strict_types=1);

namespace Src\Customer\Application\DTOs;

use Src\Customer\Domain\Entities\Customer;

final class PaginatedCustomerResult
{
    /**
     * @param  array<Customer>  $items
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
            'data' => array_map(fn (Customer $c) => $c->toArray(), $this->items),
            'pagination' => [
                'total' => $this->total,
                'current_page' => $this->currentPage,
                'per_page' => $this->perPage,
                'last_page' => $this->lastPage,
            ],
        ];
    }
}
