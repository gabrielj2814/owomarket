<?php

declare(strict_types=1);

namespace Src\Coupon\Application\DTOs;

final class CouponFilterCriteria
{
    public function __construct(
        public readonly ?string $search = null,
        public readonly ?string $type = null,
        public readonly ?bool $isActive = null,
        public readonly ?string $validDate = null,
        public readonly int $page = 1,
        public readonly int $perPage = 10,
        public readonly string $sortBy = 'created_at',
        public readonly string $sortDirection = 'desc'
    ) {}
}
