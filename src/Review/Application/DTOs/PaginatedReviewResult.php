<?php

declare(strict_types=1);

namespace Src\Review\Application\DTOs;

use Src\Review\Domain\Entities\ProductReview;

final class PaginatedReviewResult
{
    /**
     * @param  array<ProductReview>  $items
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
            'data' => array_map(fn (ProductReview $review) => $review->toArray(), $this->items),
            'total' => $this->total,
            'per_page' => $this->perPage,
            'current_page' => $this->currentPage,
            'last_page' => $this->lastPage,
        ];
    }
}
