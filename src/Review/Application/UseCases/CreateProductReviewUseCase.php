<?php

declare(strict_types=1);

namespace Src\Review\Application\UseCases;

use Src\Review\Application\DTOs\CreateReviewData;
use Src\Review\Application\Repositories\ReviewRepositoryInterface;
use Src\Review\Domain\Entities\ProductReview;
use Src\Review\Domain\Exceptions\DuplicateReviewException;
use Src\Review\Domain\ValueObjects\Rating;
use Src\Review\Domain\ValueObjects\ReviewId;

final class CreateProductReviewUseCase
{
    public function __construct(
        private readonly ReviewRepositoryInterface $repository
    ) {}

    public function execute(CreateReviewData $data): ProductReview
    {
        // Prevent duplicate review for same customer & product
        $existing = $this->repository->findByCustomerAndProduct($data->customerId, $data->productId);
        if ($existing !== null) {
            throw DuplicateReviewException::forCustomerAndProduct($data->customerId, $data->productId);
        }

        $review = ProductReview::create(
            productId: $data->productId,
            customerId: $data->customerId,
            rating: Rating::fromInt($data->rating),
            orderId: $data->orderId,
            title: $data->title,
            comment: $data->comment,
            isApproved: $data->isApproved,
            isVerified: $data->isVerified || ! empty($data->orderId),
            id: $data->id !== null ? ReviewId::fromString($data->id) : null
        );

        $this->repository->save($review);

        return $review;
    }
}
