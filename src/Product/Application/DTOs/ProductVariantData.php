<?php

declare(strict_types=1);

namespace Src\Product\Application\DTOs;

final class ProductVariantData
{
    /**
     * @param  array<string, string>  $attributes
     * @param  string[]  $attributeValueIds
     */
    public function __construct(
        public readonly string $sku,
        public readonly float $price,
        public readonly ?float $comparePrice = null,
        public readonly ?float $costPrice = null,
        public readonly int $quantity = 0,
        public readonly ?string $image = null,
        public readonly ?float $weight = null,
        public readonly array $attributes = [],
        public readonly array $attributeValueIds = [],
        public readonly ?string $id = null
    ) {}
}
