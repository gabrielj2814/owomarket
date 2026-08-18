<?php

declare(strict_types=1);

namespace Src\Customer\Domain\Entities;

use InvalidArgumentException;
use Src\Customer\Domain\ValueObjects\AddressId;
use Src\Customer\Domain\ValueObjects\AddressType;

final class CustomerAddress
{
    public function __construct(
        private readonly AddressId $id,
        private AddressType $type,
        private string $firstName,
        private string $lastName,
        private string $addressLine1,
        private string $city,
        private string $state,
        private string $postalCode,
        private string $country,
        private ?string $addressLine2 = null,
        private ?string $company = null,
        private ?string $phone = null,
        private bool $isDefault = false,
        private ?string $createdAt = null,
        private ?string $updatedAt = null
    ) {
        if (empty(trim($this->firstName))) {
            throw new InvalidArgumentException('El nombre en la dirección no puede estar vacío.');
        }
        if (empty(trim($this->lastName))) {
            throw new InvalidArgumentException('El apellido en la dirección no puede estar vacío.');
        }
        if (empty(trim($this->addressLine1))) {
            throw new InvalidArgumentException('La dirección (línea 1) no puede estar vacía.');
        }
        if (empty(trim($this->city))) {
            throw new InvalidArgumentException('La ciudad no puede estar vacía.');
        }
        if (empty(trim($this->country))) {
            throw new InvalidArgumentException('El país no puede estar vacío.');
        }
    }

    public static function create(
        string $firstName,
        string $lastName,
        string $addressLine1,
        string $city,
        string $state,
        string $postalCode,
        string $country,
        string $type = 'shipping',
        ?string $addressLine2 = null,
        ?string $company = null,
        ?string $phone = null,
        bool $isDefault = false,
        ?AddressId $id = null
    ): self {
        return new self(
            id: $id ?? AddressId::random(),
            type: AddressType::fromString($type),
            firstName: trim($firstName),
            lastName: trim($lastName),
            addressLine1: trim($addressLine1),
            city: trim($city),
            state: trim($state),
            postalCode: trim($postalCode),
            country: trim($country),
            addressLine2: $addressLine2 ? trim($addressLine2) : null,
            company: $company ? trim($company) : null,
            phone: $phone ? trim($phone) : null,
            isDefault: $isDefault
        );
    }

    public function id(): AddressId
    {
        return $this->id;
    }

    public function type(): AddressType
    {
        return $this->type;
    }

    public function firstName(): string
    {
        return $this->firstName;
    }

    public function lastName(): string
    {
        return $this->lastName;
    }

    public function fullName(): string
    {
        return trim("{$this->firstName} {$this->lastName}");
    }

    public function company(): ?string
    {
        return $this->company;
    }

    public function addressLine1(): string
    {
        return $this->addressLine1;
    }

    public function addressLine2(): ?string
    {
        return $this->addressLine2;
    }

    public function city(): string
    {
        return $this->city;
    }

    public function state(): string
    {
        return $this->state;
    }

    public function postalCode(): string
    {
        return $this->postalCode;
    }

    public function country(): string
    {
        return $this->country;
    }

    public function phone(): ?string
    {
        return $this->phone;
    }

    public function isDefault(): bool
    {
        return $this->isDefault;
    }

    public function markAsDefault(): void
    {
        $this->isDefault = true;
    }

    public function unmarkAsDefault(): void
    {
        $this->isDefault = false;
    }

    public function update(
        string $firstName,
        string $lastName,
        string $addressLine1,
        string $city,
        string $state,
        string $postalCode,
        string $country,
        string $type,
        ?string $addressLine2 = null,
        ?string $company = null,
        ?string $phone = null,
        bool $isDefault = false
    ): void {
        if (empty(trim($firstName))) {
            throw new InvalidArgumentException('El nombre en la dirección no puede estar vacío.');
        }
        if (empty(trim($lastName))) {
            throw new InvalidArgumentException('El apellido en la dirección no puede estar vacío.');
        }
        if (empty(trim($addressLine1))) {
            throw new InvalidArgumentException('La dirección (línea 1) no puede estar vacía.');
        }
        if (empty(trim($city))) {
            throw new InvalidArgumentException('La ciudad no puede estar vacía.');
        }
        if (empty(trim($country))) {
            throw new InvalidArgumentException('El país no puede estar vacío.');
        }

        $this->firstName = trim($firstName);
        $this->lastName = trim($lastName);
        $this->addressLine1 = trim($addressLine1);
        $this->city = trim($city);
        $this->state = trim($state);
        $this->postalCode = trim($postalCode);
        $this->country = trim($country);
        $this->type = AddressType::fromString($type);
        $this->addressLine2 = $addressLine2 ? trim($addressLine2) : null;
        $this->company = $company ? trim($company) : null;
        $this->phone = $phone ? trim($phone) : null;
        $this->isDefault = $isDefault;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id->value(),
            'type' => $this->type->value(),
            'first_name' => $this->firstName,
            'last_name' => $this->lastName,
            'full_name' => $this->fullName(),
            'company' => $this->company,
            'address_line_1' => $this->addressLine1,
            'address_line_2' => $this->addressLine2,
            'city' => $this->city,
            'state' => $this->state,
            'postal_code' => $this->postalCode,
            'country' => $this->country,
            'phone' => $this->phone,
            'is_default' => $this->isDefault,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }
}
