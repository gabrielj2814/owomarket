<?php

declare(strict_types=1);

namespace Src\Review\Application\UseCases;

use Src\Review\Application\DTOs\RespondReviewData;
use Src\Review\Application\Repositories\ReviewRepositoryInterface;
use Src\Review\Domain\Entities\ProductReview;
use Src\Review\Domain\Exceptions\ReviewNotFoundException;
use Src\Review\Domain\ValueObjects\ReviewId;

final class RespondReviewUseCase
{
    public function __construct(
        private readonly ReviewRepositoryInterface $repository
    ) {}

    public function execute(RespondReviewData $data): ProductReview
    {
        $reviewId = ReviewId::fromString($data->id);
        $review = $this->repository->findById($reviewId);

        if ($review === null) {
            throw ReviewNotFoundException::forId($data->id);
        }

        if (trim($data->response) === '') {
            $review->clearResponse();
        } else {
            $review->respond($data->response);
        }

        $this->repository->save($review);

        return $review;
    }
}
