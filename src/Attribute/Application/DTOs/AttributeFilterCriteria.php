<?php

declare(strict_types=1);

namespace Src\Attribute\Application\DTOs;

final class AttributeFilterCriteria
{
    public function __construct(
        public readonly ?string $search = null,
        public readonly ?string $type = null,
        public readonly ?bool $isFilterable = null,
        public readonly ?bool $isVisible = null,
        public readonly int $page = 1,
        public readonly int $perPage = 10,
        public readonly string $sortBy = 'position',
        public readonly string $sortDirection = 'asc'
    ) {}
}
