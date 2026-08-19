<?php

declare(strict_types=1);

namespace Src\Tax\Domain\ValueObjects;

use InvalidArgumentException;

final class TaxRatePercentage
{
    private function __construct(private readonly float $value) {}

    public static function create(float $value): self
    {
        if ($value < 0 || $value > 100) {
            throw new InvalidArgumentException('La tasa de impuesto debe estar entre 0% y 100%.');
        }

        return new self(round($value, 2));
    }

    public function value(): float
    {
        return $this->value;
    }
}
