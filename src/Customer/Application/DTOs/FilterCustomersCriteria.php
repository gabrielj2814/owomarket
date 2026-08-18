<?php

declare(strict_types=1);

namespace Src\Customer\Application\DTOs;

use Spatie\LaravelData\Data;

final class FilterCustomersCriteria extends Data
{
    public function __construct(
        public ?string $search = null,
        public ?bool $is_active = null,
        public ?bool $accepts_marketing = null,
        public ?string $gender = null,
        public string $sort_by = 'created_at',
        public string $sort_direction = 'desc',
        public int $page = 1,
        public int $per_page = 15
    ) {}
}
