<?php

declare(strict_types=1);

namespace Src\Billing\Domain\Entities;

use InvalidArgumentException;
use Src\Billing\Domain\ValueObjects\BillingAddress;
use Src\Billing\Domain\ValueObjects\BillingEmail;
use Src\Billing\Domain\ValueObjects\BillingProfileId;
use Src\Billing\Domain\ValueObjects\TaxId;

final class BillingProfile
{
    public function __construct(
        private readonly BillingProfileId $id,
        private string $legalName,
        private TaxId $taxId,
        private BillingEmail $billingEmail,
        private ?string $phone,
        private BillingAddress $address,
        private string $invoicePrefix,
        private int $nextInvoiceNumber,
        private ?string $invoiceFooterNotes = null,
        private ?string $logoPath = null,
        private ?array $metadata = null
    ) {
        if (empty(trim($this->legalName))) {
            throw new InvalidArgumentException('La Razón Social o Nombre Legal es obligatorio.');
        }
        if (empty(trim($this->invoicePrefix))) {
            $this->invoicePrefix = 'FAC-';
        }
        if ($this->nextInvoiceNumber < 1) {
            $this->nextInvoiceNumber = 1;
        }
    }

    public static function create(
        string $legalName,
        string $taxId,
        string $billingEmail,
        ?string $phone,
        array|BillingAddress $address,
        string $invoicePrefix = 'FAC-',
        int $nextInvoiceNumber = 1,
        ?string $invoiceFooterNotes = null,
        ?string $logoPath = null,
        ?array $metadata = null,
        ?string $id = null
    ): self {
        $addressVo = $address instanceof BillingAddress ? $address : BillingAddress::fromArray($address);

        return new self(
            id: $id ? BillingProfileId::fromString($id) : BillingProfileId::random(),
            legalName: $legalName,
            taxId: TaxId::fromString($taxId),
            billingEmail: BillingEmail::fromString($billingEmail),
            phone: $phone,
            address: $addressVo,
            invoicePrefix: ! empty($invoicePrefix) ? strtoupper(trim($invoicePrefix)) : 'FAC-',
            nextInvoiceNumber: max(1, $nextInvoiceNumber),
            invoiceFooterNotes: $invoiceFooterNotes,
            logoPath: $logoPath,
            metadata: $metadata
        );
    }

    public function update(
        string $legalName,
        string $taxId,
        string $billingEmail,
        ?string $phone,
        array|BillingAddress $address,
        string $invoicePrefix,
        int $nextInvoiceNumber,
        ?string $invoiceFooterNotes = null,
        ?string $logoPath = null,
        ?array $metadata = null
    ): void {
        if (empty(trim($legalName))) {
            throw new InvalidArgumentException('La Razón Social es obligatoria.');
        }

        $this->legalName = trim($legalName);
        $this->taxId = TaxId::fromString($taxId);
        $this->billingEmail = BillingEmail::fromString($billingEmail);
        $this->phone = $phone;
        $this->address = $address instanceof BillingAddress ? $address : BillingAddress::fromArray($address);
        $this->invoicePrefix = ! empty($invoicePrefix) ? strtoupper(trim($invoicePrefix)) : 'FAC-';
        $this->nextInvoiceNumber = max(1, $nextInvoiceNumber);
        $this->invoiceFooterNotes = $invoiceFooterNotes;
        if ($logoPath !== null) {
            $this->logoPath = $logoPath;
        }
        if ($metadata !== null) {
            $this->metadata = $metadata;
        }
    }

    /**
     * Consume el siguiente número correlativo y lo incrementa.
     *
     * @return string Número de factura formateado (ej: FAC-2026-000001)
     */
    public function generateAndIncrementInvoiceNumber(): string
    {
        $currentNumber = $this->nextInvoiceNumber;
        $this->nextInvoiceNumber++;

        $year = date('Y');
        $formattedCorrelative = str_pad((string) $currentNumber, 6, '0', STR_PAD_LEFT);

        return "{$this->invoicePrefix}{$year}-{$formattedCorrelative}";
    }

    public function id(): BillingProfileId
    {
        return $this->id;
    }

    public function legalName(): string
    {
        return $this->legalName;
    }

    public function taxId(): TaxId
    {
        return $this->taxId;
    }

    public function billingEmail(): BillingEmail
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

    public function nextInvoiceNumber(): int
    {
        return $this->nextInvoiceNumber;
    }

    public function invoiceFooterNotes(): ?string
    {
        return $this->invoiceFooterNotes;
    }

    public function logoPath(): ?string
    {
        return $this->logoPath;
    }

    public function metadata(): ?array
    {
        return $this->metadata;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id->value(),
            'legal_name' => $this->legalName,
            'tax_id' => $this->taxId->value(),
            'billing_email' => $this->billingEmail->value(),
            'phone' => $this->phone,
            'address' => $this->address->toArray(),
            'invoice_prefix' => $this->invoicePrefix,
            'next_invoice_number' => $this->nextInvoiceNumber,
            'invoice_footer_notes' => $this->invoiceFooterNotes,
            'logo_path' => $this->logoPath,
            'metadata' => $this->metadata,
        ];
    }
}
