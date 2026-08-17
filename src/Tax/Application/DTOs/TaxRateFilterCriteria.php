<?php

declare(strict_types=1);

namespace Src\Tax\Application\DTOs;

final class TaxRateFilterCriteria
{
    public function __construct(
        public readonly ?string $search = null,
        public readonly ?string $country = null,
        public readonly ?string $state = null,
        public readonly ?bool $isActive = null,
        public readonly int $page = 1,
        public readonly int $perPage = 10,
        public readonly string $sortBy = 'priority',
        public readonly string $sortDirection = 'asc'
    ) {}
}
