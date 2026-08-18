<?php

declare(strict_types=1);

namespace Src\Billing\Application\DTOs;

use Spatie\LaravelData\Data;

final class FilterInvoicesCriteria extends Data
{
    public function __construct(
        public ?string $search = null,
        public ?string $status = null,
        public ?string $payment_status = null,
        public ?string $payment_method = null,
        public ?string $date_from = null,
        public ?string $date_to = null,
        public ?float $min_total = null,
        public ?float $max_total = null,
        public string $sort_by = 'created_at',
        public string $sort_direction = 'desc',
        public int $page = 1,
        public int $per_page = 15
    ) {}
}
