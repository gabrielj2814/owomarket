<?php

declare(strict_types=1);

namespace Src\Billing\Domain\Entities;

use InvalidArgumentException;
use Src\Billing\Domain\ValueObjects\InvoiceItemId;

final class InvoiceItem
{
    public function __construct(
        private readonly InvoiceItemId $id,
        private readonly ?string $productId,
        private readonly ?string $productVariantId,
        private readonly string $description,
        private readonly ?string $sku,
        private readonly int $quantity,
        private readonly float $unitPrice,
        private readonly float $taxRate,
        private readonly float $taxAmount,
        private readonly float $discountAmount,
        private readonly float $subtotal,
        private readonly float $total
    ) {
        if (empty(trim($this->description))) {
            throw new InvalidArgumentException('La descripción del ítem de factura es obligatoria.');
        }
        if ($this->quantity < 1) {
            throw new InvalidArgumentException('La cantidad del ítem debe ser al menos 1.');
        }
        if ($this->unitPrice < 0) {
            throw new InvalidArgumentException('El precio unitario no puede ser negativo.');
        }
    }

    public static function create(
        string $description,
        int $quantity,
        float $unitPrice,
        float $taxRate = 0.0,
        float $discountAmount = 0.0,
        ?string $productId = null,
        ?string $productVariantId = null,
        ?string $sku = null,
        ?string $id = null
    ): self {
        $subtotal = round($quantity * $unitPrice, 2);
        $taxableAmount = max(0.0, $subtotal - $discountAmount);
        $taxAmount = round($taxableAmount * ($taxRate / 100), 2);
        $total = round($taxableAmount + $taxAmount, 2);

        return new self(
            id: $id ? InvoiceItemId::fromString($id) : InvoiceItemId::random(),
            productId: $productId,
            productVariantId: $productVariantId,
            description: trim($description),
            sku: $sku ? strtoupper(trim($sku)) : null,
            quantity: $quantity,
            unitPrice: round($unitPrice, 2),
            taxRate: round($taxRate, 2),
            taxAmount: $taxAmount,
            discountAmount: round($discountAmount, 2),
            subtotal: $subtotal,
            total: $total
        );
    }

    public function id(): InvoiceItemId
    {
        return $this->id;
    }

    public function productId(): ?string
    {
        return $this->productId;
    }

    public function productVariantId(): ?string
    {
        return $this->productVariantId;
    }

    public function description(): string
    {
        return $this->description;
    }

    public function sku(): ?string
    {
        return $this->sku;
    }

    public function quantity(): int
    {
        return $this->quantity;
    }

    public function unitPrice(): float
    {
        return $this->unitPrice;
    }

    public function taxRate(): float
    {
        return $this->taxRate;
    }

    public function taxAmount(): float
    {
        return $this->taxAmount;
    }

    public function discountAmount(): float
    {
        return $this->discountAmount;
    }

    public function subtotal(): float
    {
        return $this->subtotal;
    }

    public function total(): float
    {
        return $this->total;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id->value(),
            'product_id' => $this->productId,
            'product_variant_id' => $this->productVariantId,
            'description' => $this->description,
            'sku' => $this->sku,
            'quantity' => $this->quantity,
            'unit_price' => $this->unitPrice,
            'tax_rate' => $this->taxRate,
            'tax_amount' => $this->taxAmount,
            'discount_amount' => $this->discountAmount,
            'subtotal' => $this->subtotal,
            'total' => $this->total,
        ];
    }
}
