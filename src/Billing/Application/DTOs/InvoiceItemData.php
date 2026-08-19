<?php

declare(strict_types=1);

namespace Src\Billing\Application\DTOs;

use Spatie\LaravelData\Data;
use Src\Billing\Domain\Entities\InvoiceItem;

final class InvoiceItemData extends Data
{
    public function __construct(
        public string $description,
        public int $quantity,
        public float $unit_price,
        public float $tax_rate = 0.0,
        public float $discount_amount = 0.0,
        public ?string $product_id = null,
        public ?string $product_variant_id = null,
        public ?string $sku = null
    ) {}

    public function toDomain(): InvoiceItem
    {
        return InvoiceItem::create(
            description: $this->description,
            quantity: $this->quantity,
            unitPrice: $this->unit_price,
            taxRate: $this->tax_rate,
            discountAmount: $this->discount_amount,
            productId: $this->product_id,
            productVariantId: $this->product_variant_id,
            sku: $this->sku
        );
    }
}
