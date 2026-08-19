<?php

declare(strict_types=1);

namespace Src\Order\Application\DTOs;

final class OrderItemInputData
{
    public function __construct(
        public readonly string $productId,
        public readonly string $productName,
        public readonly string $sku,
        public readonly float $price,
        public readonly int $quantity,
        public readonly ?string $productVariantId = null,
        public readonly ?array $attributes = null,
        public readonly ?string $id = null
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            productId: (string) ($data['product_id'] ?? ''),
            productName: (string) ($data['product_name'] ?? ''),
            sku: (string) ($data['sku'] ?? ''),
            price: (float) ($data['price'] ?? 0.0),
            quantity: (int) ($data['quantity'] ?? 1),
            productVariantId: isset($data['product_variant_id']) && ! empty($data['product_variant_id']) ? (string) $data['product_variant_id'] : null,
            attributes: isset($data['attributes']) && is_array($data['attributes']) ? $data['attributes'] : null,
            id: isset($data['id']) && ! empty($data['id']) ? (string) $data['id'] : null
        );
    }
}
