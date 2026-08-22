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

    /**
     * Sólo los elementos. Los contadores ya son propiedades públicas de este DTO, así que
     * duplicarlos aquí dentro era lo que producía un sobre de paginación distinto por
     * módulo (hallazgo N37). Quien responde es `ApiResponse::paginated()`, que es el único
     * sitio donde vive el formato.
     */
    public function itemsToArray(): array
    {
        return array_map(fn (ProductReview $review) => $review->toArray(), $this->items);
    }
}
