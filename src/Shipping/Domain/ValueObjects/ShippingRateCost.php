<?php

declare(strict_types=1);

namespace Src\Shipping\Domain\ValueObjects;

use InvalidArgumentException;

final class ShippingRateCost
{
    private function __construct(private readonly float $value) {}

    public static function fromFloat(float $value): self
    {
        if ($value < 0) {
            throw new InvalidArgumentException('El costo de envío no puede ser negativo.');
        }

        return new self(round($value, 2));
    }

    public function value(): float
    {
        return $this->value;
    }
}
