<?php

declare(strict_types=1);

namespace Src\Customer\Application\DTOs;

use Spatie\LaravelData\Data;

final class UpdateCustomerData extends Data
{
    public function __construct(
        public string $name,
        public string $email,
        public ?string $phone = null,
        public ?string $birth_date = null,
        public ?string $gender = null,
        public ?bool $is_active = null,
        public ?bool $accepts_marketing = null,
        public ?array $metadata = null
    ) {}
}
