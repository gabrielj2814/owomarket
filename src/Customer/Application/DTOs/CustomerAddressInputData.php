<?php

declare(strict_types=1);

namespace Src\Customer\Application\DTOs;

use Spatie\LaravelData\Data;
use Src\Customer\Domain\Entities\CustomerAddress;
use Src\Customer\Domain\ValueObjects\AddressId;

final class CustomerAddressInputData extends Data
{
    public function __construct(
        public string $first_name,
        public string $last_name,
        public string $address_line_1,
        public string $city,
        public string $state,
        public string $postal_code,
        public string $country,
        public string $type = 'shipping',
        public ?string $address_line_2 = null,
        public ?string $company = null,
        public ?string $phone = null,
        public bool $is_default = false,
        public ?string $id = null
    ) {}

    public function toDomainEntity(): CustomerAddress
    {
        return CustomerAddress::create(
            firstName: $this->first_name,
            lastName: $this->last_name,
            addressLine1: $this->address_line_1,
            city: $this->city,
            state: $this->state,
            postalCode: $this->postal_code,
            country: $this->country,
            type: $this->type,
            addressLine2: $this->address_line_2,
            company: $this->company,
            phone: $this->phone,
            isDefault: $this->is_default,
            id: $this->id ? AddressId::fromString($this->id) : null
        );
    }
}
