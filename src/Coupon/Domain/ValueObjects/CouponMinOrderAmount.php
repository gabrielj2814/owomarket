<?php

declare(strict_types=1);

namespace Src\Coupon\Domain\ValueObjects;

use InvalidArgumentException;

final class CouponMinOrderAmount
{
    private function __construct(private readonly ?float $value) {}

    public static function fromNullableFloat(?float $value): self
    {
        if ($value !== null && $value < 0) {
            throw new InvalidArgumentException('El monto mínimo de orden no puede ser negativo.');
        }

        return new self($value !== null ? round($value, 2) : null);
    }

    public function value(): ?float
    {
        return $this->value;
    }
}
