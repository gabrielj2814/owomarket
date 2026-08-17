<?php

declare(strict_types=1);

namespace Src\Product\Application\DTOs;

final class ProductFilterCriteria
{
    public function __construct(
        public readonly ?string $search = null,
        public readonly ?int $categoryId = null,
        public readonly ?int $brandId = null,
        public readonly ?float $minPrice = null,
        public readonly ?float $maxPrice = null,
        public readonly ?bool $isVisible = null,
        public readonly ?bool $isFeatured = null,
        public readonly ?bool $isDigital = null,
        public readonly ?bool $inStock = null,
        public readonly int $page = 1,
        public readonly int $perPage = 10,
        public readonly string $sortBy = 'created_at',
        public readonly string $sortDirection = 'desc'
    ) {}
}
