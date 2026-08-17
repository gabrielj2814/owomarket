<?php

declare(strict_types=1);

namespace Src\Product\Domain\ValueObjects;

use InvalidArgumentException;

final class ProductStock
{
    private function __construct(
        private readonly int $quantity,
        private readonly int $minQuantity = 1,
        private readonly int $maxQuantity = 100,
        private readonly bool $trackQuantity = true
    ) {}

    public static function create(
        int $quantity,
        int $minQuantity = 1,
        int $maxQuantity = 100,
        bool $trackQuantity = true
    ): self {
        if ($quantity < 0) {
            throw new InvalidArgumentException('La cantidad en stock no puede ser negativa.');
        }

        if ($minQuantity < 0) {
            throw new InvalidArgumentException('La cantidad mínima no puede ser negativa.');
        }

        if ($maxQuantity < $minQuantity) {
            throw new InvalidArgumentException('La cantidad máxima no puede ser menor a la cantidad mínima.');
        }

        return new self(
            quantity: $quantity,
            minQuantity: $minQuantity,
            maxQuantity: $maxQuantity,
            trackQuantity: $trackQuantity
        );
    }

    public function quantity(): int
    {
        return $this->quantity;
    }

    public function minQuantity(): int
    {
        return $this->minQuantity;
    }

    public function maxQuantity(): int
    {
        return $this->maxQuantity;
    }

    public function trackQuantity(): bool
    {
        return $this->trackQuantity;
    }

    public function isInStock(): bool
    {
        if (! $this->trackQuantity) {
            return true;
        }

        return $this->quantity > 0;
    }

    public function withQuantity(int $newQuantity): self
    {
        return self::create(
            quantity: $newQuantity,
            minQuantity: $this->minQuantity,
            maxQuantity: $this->maxQuantity,
            trackQuantity: $this->trackQuantity
        );
    }
}
