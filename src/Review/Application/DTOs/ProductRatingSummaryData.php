<?php

declare(strict_types=1);

namespace Src\Review\Application\DTOs;

final class ProductRatingSummaryData
{
    /**
     * @param  array<int, int>  $starBreakdown  Map from 1..5 to count of reviews
     */
    public function __construct(
        public readonly ?string $productId,
        public readonly int $totalReviews,
        public readonly float $averageRating,
        public readonly array $starBreakdown = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0]
    ) {}

    public function toArray(): array
    {
        return [
            'product_id' => $this->productId,
            'total_reviews' => $this->totalReviews,
            'average_rating' => $this->averageRating,
            'star_breakdown' => $this->starBreakdown,
        ];
    }
}
