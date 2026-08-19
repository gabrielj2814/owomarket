<?php

declare(strict_types=1);

namespace Src\Brand\Application\DTOs;

final class BrandFilterCriteria
{
    public function __construct(
        public readonly ?string $search = null,
        public readonly ?bool $isActive = null,
        public readonly int $page = 1,
        public readonly int $perPage = 10,
        public readonly string $sortBy = 'id',
        public readonly string $sortDirection = 'desc'
    ) {}
}
