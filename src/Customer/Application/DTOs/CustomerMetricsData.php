<?php

declare(strict_types=1);

namespace Src\Customer\Application\DTOs;

use Spatie\LaravelData\Data;

final class CustomerMetricsData extends Data
{
    public function __construct(
        public int $total_customers,
        public int $active_customers,
        public int $marketing_subscribers,
        public int $new_this_month
    ) {}
}
