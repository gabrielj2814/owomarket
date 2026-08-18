<?php

declare(strict_types=1);

namespace Src\Billing\Domain\ValueObjects;

use InvalidArgumentException;

final class IssuerFiscalData
{
    public function __construct(
        private readonly string $legalName,
        private readonly string $taxId,
        private readonly string $billingEmail,
        private readonly ?string $phone,
        private readonly BillingAddress $address,
        private readonly string $invoicePrefix,
        private readonly ?string $invoiceFooterNotes = null,
        private readonly ?string $logoPath = null
    ) {
        if (empty(trim($this->legalName))) {
            throw new InvalidArgumentException('La razón social del emisor es obligatoria.');
        }
        if (empty(trim($this->taxId))) {
            throw new InvalidArgumentException('El identificador fiscal del emisor es obligatorio.');
        }
    }

    public static function fromArray(array $data): self
    {
        $address = isset($data['address']) && is_array($data['address'])
            ? BillingAddress::fromArray($data['address'])
            : BillingAddress::fromArray($data);

        return new self(
            legalName: (string) ($data['legal_name'] ?? ''),
            taxId: (string) ($data['tax_id'] ?? ''),
            billingEmail: (string) ($data['billing_email'] ?? ''),
            phone: $data['phone'] ?? null,
            address: $address,
            invoicePrefix: (string) ($data['invoice_prefix'] ?? 'FAC-'),
            invoiceFooterNotes: $data['invoice_footer_notes'] ?? null,
            logoPath: $data['logo_path'] ?? null
        );
    }

    public function toArray(): array
    {
        return [
            'legal_name' => $this->legalName,
            'tax_id' => $this->taxId,
            'billing_email' => $this->billingEmail,
            'phone' => $this->phone,
            'address' => $this->address->toArray(),
            'invoice_prefix' => $this->invoicePrefix,
            'invoice_footer_notes' => $this->invoiceFooterNotes,
            'logo_path' => $this->logoPath,
        ];
    }

    public function legalName(): string
    {
        return $this->legalName;
    }

    public function taxId(): string
    {
        return $this->taxId;
    }

    public function billingEmail(): string
    {
        return $this->billingEmail;
    }

    public function phone(): ?string
    {
        return $this->phone;
    }

    public function address(): BillingAddress
    {
        return $this->address;
    }

    public function invoicePrefix(): string
    {
        return $this->invoicePrefix;
    }

    public function invoiceFooterNotes(): ?string
    {
        return $this->invoiceFooterNotes;
    }

    public function logoPath(): ?string
    {
        return $this->logoPath;
    }
}
