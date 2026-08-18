<?php

declare(strict_types=1);

namespace Src\Billing\Domain\ValueObjects;

use InvalidArgumentException;

final class CustomerFiscalData
{
    public function __construct(
        private readonly string $name,
        private readonly ?string $taxId,
        private readonly string $email,
        private readonly BillingAddress $address
    ) {
        if (empty(trim($this->name))) {
            throw new InvalidArgumentException('El nombre o razón social del cliente es obligatorio.');
        }
        if (empty(trim($this->email))) {
            throw new InvalidArgumentException('El correo del cliente es obligatorio.');
        }
    }

    public static function fromArray(array $data): self
    {
        $address = isset($data['address']) && is_array($data['address'])
            ? BillingAddress::fromArray($data['address'])
            : BillingAddress::fromArray($data);

        return new self(
            name: (string) ($data['name'] ?? $data['billing_customer_name'] ?? ''),
            taxId: isset($data['tax_id']) && ! empty($data['tax_id']) ? (string) $data['tax_id'] : ($data['billing_customer_tax_id'] ?? null),
            email: (string) ($data['email'] ?? $data['billing_customer_email'] ?? ''),
            address: $address
        );
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'tax_id' => $this->taxId,
            'email' => $this->email,
            'address' => $this->address->toArray(),
        ];
    }

    public function name(): string
    {
        return $this->name;
    }

    public function taxId(): ?string
    {
        return $this->taxId;
    }

    public function email(): string
    {
        return $this->email;
    }

    public function address(): BillingAddress
    {
        return $this->address;
    }
}
