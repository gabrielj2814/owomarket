<?php

declare(strict_types=1);

namespace Src\Billing\Application\DTOs;

use Spatie\LaravelData\Data;

final class UpdateBillingProfileData extends Data
{
    public function __construct(
        public string $legal_name,
        public string $tax_id,
        public string $billing_email,
        public ?string $phone = null,
        public string $address_line_1 = '',
        public ?string $address_line_2 = null,
        public string $city = '',
        public string $state = '',
        public string $postal_code = '',
        public string $country = '',
        public string $invoice_prefix = 'FAC-',
        public int $next_invoice_number = 1,
        public ?string $invoice_footer_notes = null,
        public ?string $logo_path = null,
        public ?array $metadata = null
    ) {}

    public function toAddressArray(): array
    {
        return [
            'address_line_1' => $this->address_line_1,
            'address_line_2' => $this->address_line_2,
            'city' => $this->city,
            'state' => $this->state,
            'postal_code' => $this->postal_code,
            'country' => $this->country,
        ];
    }
}
