<?php

declare(strict_types=1);

namespace Src\Product\Domain\ValueObjects;

use InvalidArgumentException;

final class ProductPrice
{
    private function __construct(
        private readonly float $price,
        private readonly ?float $comparePrice = null,
        private readonly ?float $costPrice = null
    ) {}

    public static function create(
        float $price,
        ?float $comparePrice = null,
        ?float $costPrice = null
    ): self {
        if ($price < 0) {
            throw new InvalidArgumentException('El precio del producto no puede ser negativo.');
        }

        if ($comparePrice !== null && $comparePrice < 0) {
            throw new InvalidArgumentException('El precio comparativo no puede ser negativo.');
        }

        if ($costPrice !== null && $costPrice < 0) {
            throw new InvalidArgumentException('El costo del producto no puede ser negativo.');
        }

        return new self(
            price: round($price, 2),
            comparePrice: $comparePrice !== null ? round($comparePrice, 2) : null,
            costPrice: $costPrice !== null ? round($costPrice, 2) : null
        );
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

    public function hasDiscount(): bool
    {
        return $this->comparePrice !== null && $this->comparePrice > $this->price;
    }

    public function discountPercentage(): float
    {
        if (! $this->hasDiscount()) {
            return 0.0;
        }

        return round((($this->comparePrice - $this->price) / $this->comparePrice) * 100, 2);
    }
}
