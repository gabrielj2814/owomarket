<?php

declare(strict_types=1);

namespace Src\Review\Application\UseCases;

use Src\Review\Application\DTOs\CreateReviewData;
use Src\Review\Application\Repositories\ReviewRepositoryInterface;
use Src\Review\Application\Service\VerifiedPurchaseChecker;
use Src\Review\Domain\Entities\ProductReview;
use Src\Review\Domain\Exceptions\DuplicateReviewException;
use Src\Review\Domain\ValueObjects\Rating;
use Src\Review\Domain\ValueObjects\ReviewId;

final class CreateProductReviewUseCase
{
    public function __construct(
        private readonly ReviewRepositoryInterface $repository,
        private readonly VerifiedPurchaseChecker $verifiedPurchaseChecker
    ) {}

    public function execute(CreateReviewData $data): ProductReview
    {
        // Prevent duplicate review for same customer & product
        $existing = $this->repository->findByCustomerAndProduct($data->customerId, $data->productId);
        if ($existing !== null) {
            throw DuplicateReviewException::forCustomerAndProduct($data->customerId, $data->productId);
        }

        // Hallazgo B2: la insignia de "compra verificada" la concede el
        // servidor, no el cliente. Antes bastaba con `! empty($data->orderId)`
        // y `order_id` sólo se validaba con `exists:orders,id`, así que el id
        // de cualquier pedido ajeno servía.
        $isVerified = $this->verifiedPurchaseChecker->isVerifiedPurchase(
            $data->orderId,
            $data->customerId,
            $data->productId
        );

        $review = ProductReview::create(
            productId: $data->productId,
            customerId: $data->customerId,
            rating: Rating::fromInt($data->rating),
            orderId: $data->orderId,
            title: $data->title,
            comment: $data->comment,
            // Nace siempre pendiente de moderación.
            isApproved: false,
            isVerified: $isVerified,
            id: $data->id !== null ? ReviewId::fromString($data->id) : null
        );

        $this->repository->save($review);

        return $review;
    }
}
