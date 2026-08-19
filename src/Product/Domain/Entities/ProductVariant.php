<?php

declare(strict_types=1);

namespace Src\Product\Domain\Entities;

final class ProductVariant
{
    /**
     * @param  array<string, string>  $attributes
     * @param  string[]  $attributeValueIds
     */
    public function __construct(
        private ?string $id,
        private string $sku,
        private float $price,
        private ?float $comparePrice = null,
        private ?float $costPrice = null,
        private int $quantity = 0,
        private ?string $image = null,
        private ?float $weight = null,
        private array $attributes = [],
        private array $attributeValueIds = []
    ) {}

    public static function create(
        string $sku,
        float $price,
        ?float $comparePrice = null,
        ?float $costPrice = null,
        int $quantity = 0,
        ?string $image = null,
        ?float $weight = null,
        array $attributes = [],
        array $attributeValueIds = [],
        ?string $id = null
    ): self {
        return new self(
            id: $id,
            sku: strtoupper(trim($sku)),
            price: round($price, 2),
            comparePrice: $comparePrice !== null ? round($comparePrice, 2) : null,
            costPrice: $costPrice !== null ? round($costPrice, 2) : null,
            quantity: $quantity,
            image: $image,
            weight: $weight,
            attributes: $attributes,
            attributeValueIds: $attributeValueIds
        );
    }

    public function id(): ?string
    {
        return $this->id;
    }

    public function sku(): string
    {
        return $this->sku;
    }

    public function price(): float
    {
        return $this->price;
    }

    public function comparePrice(): ?float
    {
        return $this->comparePrice;
    }

    public function costPrice(): ?float
    {
        return $this->costPrice;
    }

    public function quantity(): int
    {
        return $this->quantity;
    }

    public function image(): ?string
    {
        return $this->image;
    }

    public function weight(): ?float
    {
        return $this->weight;
    }

    public function attributes(): array
    {
        return $this->attributes;
    }

    public function attributeValueIds(): array
    {
        return $this->attributeValueIds;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'sku' => $this->sku,
            'price' => $this->price,
            'compare_price' => $this->comparePrice,
            'cost_price' => $this->costPrice,
            'quantity' => $this->quantity,
            'image' => $this->image,
            'weight' => $this->weight,
            'attributes' => $this->attributes,
            'attribute_value_ids' => $this->attributeValueIds,
        ];
    }
}
