<?php

declare(strict_types=1);

namespace Src\Coupon\Domain\ValueObjects;

use InvalidArgumentException;

final class CouponValue
{
    private function __construct(private readonly float $value) {}

    public static function create(float $value, CouponType $type): self
    {
        if ($value <= 0) {
            throw new InvalidArgumentException('El valor del cupón debe ser mayor a 0.');
        }

        if ($type->isPercentage() && $value > 100) {
            throw new InvalidArgumentException('El porcentaje de descuento no puede ser mayor al 100%.');
        }

        return new self(round($value, 2));
    }

    public function value(): float
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return abs($this->value - $other->value) < 0.0001;
    }
}
