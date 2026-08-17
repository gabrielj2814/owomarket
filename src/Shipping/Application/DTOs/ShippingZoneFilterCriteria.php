<?php

declare(strict_types=1);

namespace Src\Shipping\Application\DTOs;

final class ShippingZoneFilterCriteria
{
    public function __construct(
        public readonly ?string $search = null,
        public readonly ?bool $isActive = null,
        public readonly int $page = 1,
        public readonly int $perPage = 10,
        public readonly string $sortBy = 'priority',
        public readonly string $sortDirection = 'asc'
    ) {}
}
