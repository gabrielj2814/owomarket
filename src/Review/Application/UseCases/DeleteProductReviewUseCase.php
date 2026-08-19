<?php

declare(strict_types=1);

namespace Src\Review\Application\UseCases;

use Src\Review\Application\Repositories\ReviewRepositoryInterface;
use Src\Review\Domain\Exceptions\ReviewNotFoundException;
use Src\Review\Domain\ValueObjects\ReviewId;

final class DeleteProductReviewUseCase
{
    public function __construct(
        private readonly ReviewRepositoryInterface $repository
    ) {}

    public function execute(string $id): void
    {
        $reviewId = ReviewId::fromString($id);
        $review = $this->repository->findById($reviewId);

        if ($review === null) {
            throw ReviewNotFoundException::forId($id);
        }

        $this->repository->delete($reviewId);
    }
}
