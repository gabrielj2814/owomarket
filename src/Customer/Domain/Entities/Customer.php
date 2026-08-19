<?php

declare(strict_types=1);

namespace Src\Customer\Domain\Entities;

use Src\Customer\Domain\Exceptions\CustomerAddressNotFoundException;
use Src\Customer\Domain\ValueObjects\AddressId;
use Src\Customer\Domain\ValueObjects\AddressType;
use Src\Customer\Domain\ValueObjects\BirthDate;
use Src\Customer\Domain\ValueObjects\CustomerEmail;
use Src\Customer\Domain\ValueObjects\CustomerId;
use Src\Customer\Domain\ValueObjects\CustomerName;
use Src\Customer\Domain\ValueObjects\CustomerPhone;
use Src\Customer\Domain\ValueObjects\Gender;

final class Customer
{
    /**
     * @param  array<CustomerAddress>  $addresses
     */
    public function __construct(
        private readonly CustomerId $id,
        private CustomerName $name,
        private CustomerEmail $email,
        private ?CustomerPhone $phone = null,
        private ?BirthDate $birthDate = null,
        private ?Gender $gender = null,
        private bool $isActive = true,
        private bool $acceptsMarketing = false,
        private ?array $metadata = null,
        private array $addresses = [],
        private ?string $createdAt = null,
        private ?string $updatedAt = null
    ) {}

    /**
     * @param  array<CustomerAddress>  $addresses
     */
    public static function create(
        string $name,
        string $email,
        ?string $phone = null,
        ?string $birthDate = null,
        ?string $gender = null,
        bool $isActive = true,
        bool $acceptsMarketing = false,
        ?array $metadata = null,
        array $addresses = [],
        ?CustomerId $id = null
    ): self {
        return new self(
            id: $id ?? CustomerId::random(),
            name: CustomerName::fromString($name),
            email: CustomerEmail::fromString($email),
            phone: CustomerPhone::nullable($phone),
            birthDate: BirthDate::nullable($birthDate),
            gender: Gender::nullable($gender),
            isActive: $isActive,
            acceptsMarketing: $acceptsMarketing,
            metadata: $metadata,
            addresses: $addresses
        );
    }

    public function id(): CustomerId
    {
        return $this->id;
    }

    public function name(): CustomerName
    {
        return $this->name;
    }

    public function email(): CustomerEmail
    {
        return $this->email;
    }

    public function phone(): ?CustomerPhone
    {
        return $this->phone;
    }

    public function birthDate(): ?BirthDate
    {
        return $this->birthDate;
    }

    public function gender(): ?Gender
    {
        return $this->gender;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function acceptsMarketing(): bool
    {
        return $this->acceptsMarketing;
    }

    public function metadata(): ?array
    {
        return $this->metadata;
    }

    /**
     * @return array<CustomerAddress>
     */
    public function addresses(): array
    {
        return $this->addresses;
    }

    public function createdAt(): ?string
    {
        return $this->createdAt;
    }

    public function updatedAt(): ?string
    {
        return $this->updatedAt;
    }

    public function updateProfile(
        string $name,
        string $email,
        ?string $phone = null,
        ?string $birthDate = null,
        ?string $gender = null,
        ?bool $isActive = null,
        ?bool $acceptsMarketing = null,
        ?array $metadata = null
    ): void {
        $this->name = CustomerName::fromString($name);
        $this->email = CustomerEmail::fromString($email);
        $this->phone = CustomerPhone::nullable($phone);
        $this->birthDate = BirthDate::nullable($birthDate);
        $this->gender = Gender::nullable($gender);

        if ($isActive !== null) {
            $this->isActive = $isActive;
        }

        if ($acceptsMarketing !== null) {
            $this->acceptsMarketing = $acceptsMarketing;
        }

        if ($metadata !== null) {
            $this->metadata = $metadata;
        }
    }

    public function activate(): void
    {
        $this->isActive = true;
    }

    public function deactivate(): void
    {
        $this->isActive = false;
    }

    public function setMarketingPreference(bool $accepts): void
    {
        $this->acceptsMarketing = $accepts;
    }

    public function addAddress(CustomerAddress $address): void
    {
        // Si la nueva dirección es por defecto, desmarcar las existentes del mismo tipo
        if ($address->isDefault()) {
            $this->clearDefaultAddressForType($address->type());
        }

        $this->addresses[] = $address;
    }

    public function removeAddress(AddressId $addressId): void
    {
        $initialCount = count($this->addresses);
        $this->addresses = array_values(array_filter(
            $this->addresses,
            fn (CustomerAddress $addr) => ! $addr->id()->equals($addressId)
        ));

        if (count($this->addresses) === $initialCount) {
            throw CustomerAddressNotFoundException::withId($addressId);
        }
    }

    public function setDefaultAddress(AddressId $addressId): void
    {
        $found = false;
        $targetType = null;

        foreach ($this->addresses as $addr) {
            if ($addr->id()->equals($addressId)) {
                $found = true;
                $targetType = $addr->type();
                break;
            }
        }

        if (! $found || $targetType === null) {
            throw CustomerAddressNotFoundException::withId($addressId);
        }

        $this->clearDefaultAddressForType($targetType);

        foreach ($this->addresses as $addr) {
            if ($addr->id()->equals($addressId)) {
                $addr->markAsDefault();
                break;
            }
        }
    }

    public function getDefaultAddress(?AddressType $type = null): ?CustomerAddress
    {
        foreach ($this->addresses as $addr) {
            if ($addr->isDefault()) {
                if ($type === null || $addr->type()->equals($type)) {
                    return $addr;
                }
            }
        }

        return $this->addresses[0] ?? null;
    }

    private function clearDefaultAddressForType(AddressType $type): void
    {
        foreach ($this->addresses as $addr) {
            if ($addr->type()->equals($type)) {
                $addr->unmarkAsDefault();
            }
        }
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id->value(),
            'name' => $this->name->value(),
            'email' => $this->email->value(),
            'phone' => $this->phone?->value(),
            'birth_date' => $this->birthDate?->value(),
            'gender' => $this->gender?->value(),
            'is_active' => $this->isActive,
            'accepts_marketing' => $this->acceptsMarketing,
            'metadata' => $this->metadata,
            'addresses' => array_map(fn (CustomerAddress $addr) => $addr->toArray(), $this->addresses),
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }
}
