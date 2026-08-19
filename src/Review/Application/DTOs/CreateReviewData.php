<?php

declare(strict_types=1);

namespace Src\Review\Application\DTOs;

final class CreateReviewData
{
    public function __construct(
        public readonly string $productId,
        public readonly string $customerId,
        public readonly int $rating,
        public readonly ?string $orderId = null,
        public readonly ?string $title = null,
        public readonly ?string $comment = null,
        public readonly bool $isApproved = false,
        public readonly bool $isVerified = false,
        public readonly ?string $id = null
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            productId: (string) $data['product_id'],
            customerId: (string) $data['customer_id'],
            rating: (int) $data['rating'],
            orderId: isset($data['order_id']) && $data['order_id'] !== '' ? (string) $data['order_id'] : null,
            title: isset($data['title']) && $data['title'] !== '' ? (string) $data['title'] : null,
            comment: isset($data['comment']) && $data['comment'] !== '' ? (string) $data['comment'] : null,
            isApproved: (bool) ($data['is_approved'] ?? false),
            isVerified: (bool) ($data['is_verified'] ?? false),
            id: isset($data['id']) && $data['id'] !== '' ? (string) $data['id'] : null
        );
    }
}
