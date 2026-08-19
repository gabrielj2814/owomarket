<?php

declare(strict_types=1);

namespace Src\Coupon\Domain\ValueObjects;

use Src\Shared\Domain\ValueObjects\BoolValueObject;

final class CouponStatus extends BoolValueObject
{
    public static function active(): self
    {
        return new self(true);
    }

    public static function inactive(): self
    {
        return new self(false);
    }

    public static function fromBool(bool $value): self
    {
        return new self($value);
    }
}
