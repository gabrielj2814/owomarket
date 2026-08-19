<?php

declare(strict_types=1);

namespace Src\Review\Application\Repositories;

use Src\Review\Application\DTOs\FilterReviewsCriteria;
use Src\Review\Application\DTOs\PaginatedReviewResult;
use Src\Review\Application\DTOs\ProductRatingSummaryData;
use Src\Review\Domain\Entities\ProductReview;
use Src\Review\Domain\ValueObjects\ReviewId;

interface ReviewRepositoryInterface
{
    public function save(ProductReview $review): void;

    public function findById(ReviewId $id): ?ProductReview;

    public function findByCustomerAndProduct(string $customerId, string $productId): ?ProductReview;

    /**
     * @return array<ProductReview>
     */
    public function findByProductId(string $productId, bool $onlyApproved = true): array;

    public function filter(FilterReviewsCriteria $criteria): PaginatedReviewResult;

    public function getRatingSummary(?string $productId = null): ProductRatingSummaryData;

    public function delete(ReviewId $id): void;
}
