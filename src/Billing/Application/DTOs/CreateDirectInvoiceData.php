<?php

declare(strict_types=1);

namespace Src\Billing\Application\DTOs;

use Spatie\LaravelData\Data;

final class CreateDirectInvoiceData extends Data
{
    /**
     * @param  array<InvoiceItemData>  $items
     */
    public function __construct(
        public string $customer_name,
        public string $customer_email,
        public ?string $customer_tax_id = null,
        public string $customer_address_line_1 = '',
        public ?string $customer_address_line_2 = null,
        public string $customer_city = '',
        public string $customer_state = '',
        public string $customer_postal_code = '',
        public string $customer_country = '',
        public array $items = [],
        public string $payment_method = 'manual',
        public string $payment_status = 'paid',
        public string $status = 'issued',
        public ?string $issue_date = null,
        public ?string $due_date = null,
        public string $currency = 'USD',
        public ?string $notes = null,
        public ?string $order_id = null,
        public ?string $customer_id = null,
        public ?array $metadata = null,
        public ?float $exchange_rate = null,
        public ?float $commission_amount = null,
        public ?string $commission_currency = null
    ) {}

    public function toCustomerAddressArray(): array
    {
        return [
            'address_line_1' => $this->customer_address_line_1,
            'address_line_2' => $this->customer_address_line_2,
            'city' => $this->customer_city,
            'state' => $this->customer_state,
            'postal_code' => $this->customer_postal_code,
            'country' => $this->customer_country,
        ];
    }
}
