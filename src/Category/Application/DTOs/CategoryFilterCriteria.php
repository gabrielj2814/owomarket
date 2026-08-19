<?php

declare(strict_types=1);

namespace Src\Category\Application\DTOs;

final class CategoryFilterCriteria
{
    public function __construct(
        public readonly ?string $search = null,
        public readonly ?bool $isActive = null,
        public readonly ?int $parentId = null,
        public readonly ?string $fechaDesdeUTC = null,
        public readonly ?string $fechaHastaUTC = null,
        public readonly int $page = 1,
        public readonly int $perPage = 50
    ) {}
}
