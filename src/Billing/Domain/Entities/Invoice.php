<?php

declare(strict_types=1);

namespace Src\Billing\Domain\Entities;

use DateTimeImmutable;
use InvalidArgumentException;
use Src\Billing\Domain\Exceptions\InvalidInvoiceStateException;
use Src\Billing\Domain\ValueObjects\CustomerFiscalData;
use Src\Billing\Domain\ValueObjects\InvoiceDate;
use Src\Billing\Domain\ValueObjects\InvoiceId;
use Src\Billing\Domain\ValueObjects\InvoiceNumber;
use Src\Billing\Domain\ValueObjects\InvoiceStatus;
use Src\Billing\Domain\ValueObjects\IssuerFiscalData;

final class Invoice
{
    /**
     * @param  array<InvoiceItem>  $items
     */
    public function __construct(
        private readonly InvoiceId $id,
        private readonly ?string $orderId,
        private readonly ?string $customerId,
        private readonly InvoiceNumber $invoiceNumber,
        private InvoiceStatus $status,
        private InvoiceDate $date,
        private string $currency,
        private float $subtotal,
        private float $taxAmount,
        private float $discountAmount,
        private float $total,
        private string $paymentMethod,
        private string $paymentStatus,
        private ?DateTimeImmutable $paidAt,
        private CustomerFiscalData $customer,
        private IssuerFiscalData $issuer,
        private array $items = [],
        private ?string $pdfPath = null,
        private ?string $notes = null,
        private ?array $metadata = null,
        private ?float $exchangeRate = null,
        private ?float $subtotalVes = null,
        private ?float $totalVes = null,
        private ?float $subtotalUsd = null,
        private ?float $totalUsd = null,
        private ?float $commissionAmount = null,
        private ?string $commissionCurrency = null
    ) {
        if ($this->subtotal < 0) {
            throw new InvalidArgumentException('El subtotal de la factura no puede ser negativo.');
        }
        if ($this->total < 0) {
            throw new InvalidArgumentException('El total de la factura no puede ser negativo.');
        }
        if (empty(trim($this->currency))) {
            $this->currency = 'USD';
        }
    }

    /**
     * Crea una factura directa o manual emitida desde el backoffice.
     *
     * @param  array<InvoiceItem>  $items
     */
    public static function createDirect(
        string $invoiceNumber,
        array|CustomerFiscalData $customer,
        array|IssuerFiscalData $issuer,
        array $items,
        string $paymentMethod = 'manual',
        string $paymentStatus = 'paid',
        string $status = 'issued',
        ?string $issueDate = null,
        ?string $dueDate = null,
        string $currency = 'USD',
        ?string $notes = null,
        ?string $orderId = null,
        ?string $customerId = null,
        ?string $id = null,
        ?array $metadata = null,
        ?float $exchangeRate = null,
        ?float $subtotalVes = null,
        ?float $totalVes = null,
        ?float $subtotalUsd = null,
        ?float $totalUsd = null,
        ?float $commissionAmount = null,
        ?string $commissionCurrency = null
    ): self {
        if (empty($items)) {
            throw new InvalidArgumentException('Una factura debe contener al menos un ítem.');
        }

        $customerVo = $customer instanceof CustomerFiscalData ? $customer : CustomerFiscalData::fromArray($customer);
        $issuerVo = $issuer instanceof IssuerFiscalData ? $issuer : IssuerFiscalData::fromArray($issuer);
        $statusVo = InvoiceStatus::fromString($status);
        $dateVo = InvoiceDate::create($issueDate, $dueDate);
        $invNumberVo = InvoiceNumber::fromString($invoiceNumber);
        $idVo = $id ? InvoiceId::fromString($id) : InvoiceId::random();

        // Calcular totales acumulados desde los ítems
        $subtotal = 0.0;
        $taxAmount = 0.0;
        $discountAmount = 0.0;
        $total = 0.0;

        foreach ($items as $item) {
            $subtotal += $item->subtotal();
            $taxAmount += $item->taxAmount();
            $discountAmount += $item->discountAmount();
            $total += $item->total();
        }

        $paidAt = $paymentStatus === 'paid' ? new DateTimeImmutable : null;

        // Auto-calcular totales duales si no fueron provistos explícitamente pero hay exchangeRate
        if ($exchangeRate !== null && $exchangeRate > 0) {
            if ($currency === 'USD' || $currency === 'USDT' || $currency === 'USDC') {
                $subtotalUsd = $subtotalUsd ?? round($subtotal, 2);
                $totalUsd = $totalUsd ?? round($total, 2);
                $subtotalVes = $subtotalVes ?? round($subtotal * $exchangeRate, 2);
                $totalVes = $totalVes ?? round($total * $exchangeRate, 2);
            } elseif ($currency === 'VES') {
                $subtotalVes = $subtotalVes ?? round($subtotal, 2);
                $totalVes = $totalVes ?? round($total, 2);
                $subtotalUsd = $subtotalUsd ?? round($subtotal / $exchangeRate, 2);
                $totalUsd = $totalUsd ?? round($total / $exchangeRate, 2);
            }
        }

        return new self(
            id: $idVo,
            orderId: $orderId,
            customerId: $customerId,
            invoiceNumber: $invNumberVo,
            status: $statusVo,
            date: $dateVo,
            currency: strtoupper(trim($currency)),
            subtotal: round($subtotal, 2),
            taxAmount: round($taxAmount, 2),
            discountAmount: round($discountAmount, 2),
            total: round($total, 2),
            paymentMethod: $paymentMethod,
            paymentStatus: $paymentStatus,
            paidAt: $paidAt,
            customer: $customerVo,
            issuer: $issuerVo,
            items: $items,
            pdfPath: null,
            notes: $notes,
            metadata: $metadata,
            exchangeRate: $exchangeRate,
            subtotalVes: $subtotalVes,
            totalVes: $totalVes,
            subtotalUsd: $subtotalUsd,
            totalUsd: $totalUsd,
            commissionAmount: $commissionAmount,
            commissionCurrency: $commissionCurrency
        );
    }

    public function markAsPaid(?string $paymentMethod = null): void
    {
        $this->status = InvoiceStatus::paid();
        $this->paymentStatus = 'paid';
        $this->paidAt = new DateTimeImmutable;
        if ($paymentMethod) {
            $this->paymentMethod = $paymentMethod;
        }
    }

    public function cancel(string $reason = ''): void
    {
        if (! $this->status->canBeCancelled()) {
            throw InvalidInvoiceStateException::cannotBeCancelled($this->status->value());
        }

        $this->status = InvoiceStatus::cancelled();
        if (! empty($reason)) {
            $this->notes = trim(($this->notes ? "{$this->notes} | " : '')."Anulada: {$reason}");
        }
    }

    public function setPdfPath(string $path): void
    {
        $this->pdfPath = $path;
    }

    public function id(): InvoiceId
    {
        return $this->id;
    }

    public function orderId(): ?string
    {
        return $this->orderId;
    }

    public function customerId(): ?string
    {
        return $this->customerId;
    }

    public function invoiceNumber(): InvoiceNumber
    {
        return $this->invoiceNumber;
    }

    public function status(): InvoiceStatus
    {
        return $this->status;
    }

    public function date(): InvoiceDate
    {
        return $this->date;
    }

    public function currency(): string
    {
        return $this->currency;
    }

    public function subtotal(): float
    {
        return $this->subtotal;
    }

    public function taxAmount(): float
    {
        return $this->taxAmount;
    }

    public function discountAmount(): float
    {
        return $this->discountAmount;
    }

    public function total(): float
    {
        return $this->total;
    }

    public function paymentMethod(): string
    {
        return $this->paymentMethod;
    }

    public function paymentStatus(): string
    {
        return $this->paymentStatus;
    }

    public function paidAt(): ?DateTimeImmutable
    {
        return $this->paidAt;
    }

    public function customer(): CustomerFiscalData
    {
        return $this->customer;
    }

    public function issuer(): IssuerFiscalData
    {
        return $this->issuer;
    }

    /**
     * @return array<InvoiceItem>
     */
    public function items(): array
    {
        return $this->items;
    }

    public function pdfPath(): ?string
    {
        return $this->pdfPath;
    }

    public function notes(): ?string
    {
        return $this->notes;
    }

    public function metadata(): ?array
    {
        return $this->metadata;
    }

    public function exchangeRate(): ?float
    {
        return $this->exchangeRate;
    }

    public function subtotalVes(): ?float
    {
        return $this->subtotalVes;
    }

    public function totalVes(): ?float
    {
        return $this->totalVes;
    }

    public function subtotalUsd(): ?float
    {
        return $this->subtotalUsd;
    }

    public function totalUsd(): ?float
    {
        return $this->totalUsd;
    }

    public function commissionAmount(): ?float
    {
        return $this->commissionAmount;
    }

    public function commissionCurrency(): ?string
    {
        return $this->commissionCurrency;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id->value(),
            'order_id' => $this->orderId,
            'customer_id' => $this->customerId,
            'invoice_number' => $this->invoiceNumber->value(),
            'status' => $this->status->value(),
            'issue_date' => $this->date->issueDateFormatted(),
            'due_date' => $this->date->dueDateFormatted(),
            'currency' => $this->currency,
            'exchange_rate' => $this->exchangeRate,
            'subtotal' => $this->subtotal,
            'tax_amount' => $this->taxAmount,
            'discount_amount' => $this->discountAmount,
            'total' => $this->total,
            'subtotal_ves' => $this->subtotalVes,
            'total_ves' => $this->totalVes,
            'subtotal_usd' => $this->subtotalUsd,
            'total_usd' => $this->totalUsd,
            'commission_amount' => $this->commissionAmount,
            'commission_currency' => $this->commissionCurrency,
            'payment_method' => $this->paymentMethod,
            'payment_status' => $this->paymentStatus,
            'paid_at' => $this->paidAt?->format('Y-m-d H:i:s'),
            'billing_customer_name' => $this->customer->name(),
            'billing_customer_tax_id' => $this->customer->taxId(),
            'billing_customer_email' => $this->customer->email(),
            'billing_customer_address' => $this->customer->address()->toArray(),
            'issuer_snapshot' => $this->issuer->toArray(),
            'items' => array_map(fn (InvoiceItem $item) => $item->toArray(), $this->items),
            'pdf_path' => $this->pdfPath,
            'notes' => $this->notes,
            'metadata' => $this->metadata,
        ];
    }
}
