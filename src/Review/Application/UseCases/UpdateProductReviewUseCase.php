<?php

declare(strict_types=1);

namespace Src\Review\Application\UseCases;

use Src\Review\Application\DTOs\UpdateReviewData;
use Src\Review\Application\Repositories\ReviewRepositoryInterface;
use Src\Review\Domain\Entities\ProductReview;
use Src\Review\Domain\Exceptions\ReviewNotFoundException;
use Src\Review\Domain\ValueObjects\Rating;
use Src\Review\Domain\ValueObjects\ReviewId;

final class UpdateProductReviewUseCase
{
    public function __construct(
        private readonly ReviewRepositoryInterface $repository
    ) {}

    public function execute(UpdateReviewData $data): ProductReview
    {
        $reviewId = ReviewId::fromString($data->id);
        $review = $this->repository->findById($reviewId);

        if ($review === null) {
            throw ReviewNotFoundException::forId($data->id);
        }

        $review->updateContent(
            rating: Rating::fromInt($data->rating),
            title: $data->title,
            comment: $data->comment
        );

        $this->repository->save($review);

        return $review;
    }
}
