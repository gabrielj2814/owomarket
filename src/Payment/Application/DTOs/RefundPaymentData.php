<?php

declare(strict_types=1);

namespace Src\Payment\Application\DTOs;

use Spatie\LaravelData\Data;

final class RefundPaymentData extends Data
{
    public function __construct(
        public string $transaction_id,
        public float $amount,
        public string $payment_method,
        public ?string $reason = null
    ) {}
}
