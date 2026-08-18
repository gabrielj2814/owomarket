<?php

declare(strict_types=1);

namespace Src\Review\Application\UseCases;

use Src\Review\Application\Repositories\ReviewRepositoryInterface;
use Src\Review\Domain\Entities\ProductReview;
use Src\Review\Domain\Exceptions\ReviewNotFoundException;
use Src\Review\Domain\ValueObjects\ReviewId;

final class ConsultReviewByIdUseCase
{
    public function __construct(
        private readonly ReviewRepositoryInterface $repository
    ) {}

    public function execute(string $id): ProductReview
    {
        $reviewId = ReviewId::fromString($id);
        $review = $this->repository->findById($reviewId);

        if ($review === null) {
            throw ReviewNotFoundException::forId($id);
        }

        return $review;
    }
}
