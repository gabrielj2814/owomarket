<?php

declare(strict_types=1);

namespace Src\Review\Application\UseCases;

use Src\Review\Application\DTOs\ModerateReviewData;
use Src\Review\Application\Repositories\ReviewRepositoryInterface;
use Src\Review\Domain\Entities\ProductReview;
use Src\Review\Domain\Exceptions\ReviewNotFoundException;
use Src\Review\Domain\ValueObjects\ReviewId;

final class ModerateReviewUseCase
{
    public function __construct(
        private readonly ReviewRepositoryInterface $repository
    ) {}

    public function execute(ModerateReviewData $data): ProductReview
    {
        $reviewId = ReviewId::fromString($data->id);
        $review = $this->repository->findById($reviewId);

        if ($review === null) {
            throw ReviewNotFoundException::forId($data->id);
        }

        if ($data->isApproved) {
            $review->approve();
        } else {
            $review->reject();
        }

        $this->repository->save($review);

        return $review;
    }
}
