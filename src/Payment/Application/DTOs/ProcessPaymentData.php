<?php

declare(strict_types=1);

namespace Src\Payment\Application\DTOs;

use Spatie\LaravelData\Data;

final class ProcessPaymentData extends Data
{
    public function __construct(
        public float $amount,
        public string $currency,
        public string $customer_email,
        public string $customer_name,
        public string $payment_method = 'manual_transfer',
        public ?string $order_id = null,
        public ?string $description = null,
        public ?string $return_url = null,
        public ?string $cancel_url = null,
        public ?array $metadata = null
    ) {}

    public function toChargeArray(): array
    {
        return [
            'order_id' => $this->order_id,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'customer_email' => $this->customer_email,
            'customer_name' => $this->customer_name,
            'description' => $this->description,
            'return_url' => $this->return_url,
            'cancel_url' => $this->cancel_url,
            'metadata' => $this->metadata,
        ];
    }
}
