<?php

declare(strict_types=1);

namespace Src\Customer\Application\DTOs;

use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Data;

final class CreateCustomerData extends Data
{
    /**
     * @param  array<CustomerAddressInputData>  $addresses
     */
    public function __construct(
        public string $name,
        public string $email,
        public ?string $phone = null,
        public ?string $birth_date = null,
        public ?string $gender = null,
        public bool $is_active = true,
        public bool $accepts_marketing = false,
        public ?array $metadata = null,
        #[DataCollectionOf(CustomerAddressInputData::class)]
        public array $addresses = []
    ) {}
}
