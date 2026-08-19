<?php

declare(strict_types=1);

namespace Src\Billing\Domain\ValueObjects;

use InvalidArgumentException;

final class BillingAddress
{
    public function __construct(
        private readonly string $addressLine1,
        private readonly ?string $addressLine2,
        private readonly string $city,
        private readonly string $state,
        private readonly string $postalCode,
        private readonly string $country
    ) {
        if (empty(trim($this->addressLine1))) {
            throw new InvalidArgumentException('La dirección fiscal es obligatoria.');
        }
        if (empty(trim($this->city))) {
            throw new InvalidArgumentException('La ciudad es obligatoria.');
        }
        if (empty(trim($this->country))) {
            throw new InvalidArgumentException('El país es obligatorio.');
        }
    }

    public static function fromArray(array $data): self
    {
        return new self(
            addressLine1: (string) ($data['address_line_1'] ?? $data['line1'] ?? ''),
            addressLine2: isset($data['address_line_2']) ? (string) $data['address_line_2'] : ($data['line2'] ?? null),
            city: (string) ($data['city'] ?? ''),
            state: (string) ($data['state'] ?? ''),
            postalCode: (string) ($data['postal_code'] ?? $data['zip'] ?? ''),
            country: (string) ($data['country'] ?? '')
        );
    }

    public function toArray(): array
    {
        return [
            'address_line_1' => $this->addressLine1,
            'address_line_2' => $this->addressLine2,
            'city' => $this->city,
            'state' => $this->state,
            'postal_code' => $this->postalCode,
            'country' => $this->country,
        ];
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

    public function fullFormattedAddress(): string
    {
        $parts = array_filter([
            $this->addressLine1,
            $this->addressLine2,
            $this->city,
            $this->state,
            $this->postalCode,
            $this->country,
        ]);

        return implode(', ', $parts);
    }
}
