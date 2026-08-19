<?php

declare(strict_types=1);

namespace Src\Coupon\Domain\ValueObjects;

use InvalidArgumentException;

final class CouponUsageLimit
{
    private function __construct(private readonly ?int $value) {}

    public static function fromNullableInt(?int $value): self
    {
        if ($value !== null && $value < 1) {
            throw new InvalidArgumentException('El límite de uso debe ser al menos 1.');
        }

        return new self($value);
    }

    public function value(): ?int
    {
        return $this->value;
    }
}
