<?php

declare(strict_types=1);

namespace Src\Coupon\Domain\ValueObjects;

use InvalidArgumentException;
use Src\Shared\Domain\ValueObjects\StringValueObject;

final class CouponType extends StringValueObject
{
    public const TYPE_PERCENTAGE = 'percentage';

    public const TYPE_FIXED_AMOUNT = 'fixed_amount';

    public const ALLOWED_TYPES = [
        self::TYPE_PERCENTAGE,
        self::TYPE_FIXED_AMOUNT,
    ];

    public static function fromString(string $value): self
    {
        return new self($value);
    }

    public static function percentage(): self
    {
        return new self(self::TYPE_PERCENTAGE);
    }

    public static function fixedAmount(): self
    {
        return new self(self::TYPE_FIXED_AMOUNT);
    }

    public function isPercentage(): bool
    {
        return $this->value === self::TYPE_PERCENTAGE;
    }

    public function isFixedAmount(): bool
    {
        return $this->value === self::TYPE_FIXED_AMOUNT;
    }

    protected function validate(string $value): void
    {
        $normalized = mb_strtolower(trim($value));

        if (! in_array($normalized, self::ALLOWED_TYPES, true)) {
            throw new InvalidArgumentException(
                sprintf('Tipo de cupón no válido: "%s". Tipos permitidos: %s', $value, implode(', ', self::ALLOWED_TYPES))
            );
        }
    }
}
