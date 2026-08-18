<?php

declare(strict_types=1);

namespace Src\Payment\Application\DTOs;

use Spatie\LaravelData\Data;

final class PaymentGatewayInfoData extends Data
{
    public function __construct(
        public string $identifier,
        public string $display_name,
        public bool $is_offline
    ) {}
}
