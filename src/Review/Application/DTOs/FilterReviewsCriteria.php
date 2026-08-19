<?php

declare(strict_types=1);

namespace Src\Review\Application\DTOs;

final class FilterReviewsCriteria
{
    public function __construct(
        public readonly ?string $search = null,
        public readonly ?string $productId = null,
        public readonly ?string $customerId = null,
        public readonly ?int $rating = null,
        public readonly ?bool $isApproved = null,
        public readonly ?bool $isVerified = null,
        public readonly ?bool $hasResponse = null,
        public readonly int $page = 1,
        public readonly int $perPage = 15,
        public readonly string $sortBy = 'created_at',
        public readonly string $sortDirection = 'desc'
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            search: isset($data['search']) && $data['search'] !== '' ? (string) $data['search'] : null,
            productId: isset($data['product_id']) && $data['product_id'] !== '' ? (string) $data['product_id'] : null,
            customerId: isset($data['customer_id']) && $data['customer_id'] !== '' ? (string) $data['customer_id'] : null,
            rating: isset($data['rating']) && $data['rating'] !== '' ? (int) $data['rating'] : null,
            isApproved: isset($data['is_approved']) ? (bool) $data['is_approved'] : null,
            isVerified: isset($data['is_verified']) ? (bool) $data['is_verified'] : null,
            hasResponse: isset($data['has_response']) ? (bool) $data['has_response'] : null,
            page: max(1, (int) ($data['page'] ?? 1)),
            perPage: max(1, min(100, (int) ($data['per_page'] ?? 15))),
            sortBy: (string) ($data['sort_by'] ?? 'created_at'),
            sortDirection: strtolower((string) ($data['sort_direction'] ?? 'desc')) === 'asc' ? 'asc' : 'desc'
        );
    }
}
