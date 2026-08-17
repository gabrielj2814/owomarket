<?php

declare(strict_types=1);

namespace Src\Coupon\Domain\ValueObjects;

use InvalidArgumentException;
use Src\Shared\Domain\ValueObjects\StringValueObject;

final class CouponCode extends StringValueObject
{
    private const MIN_LENGTH = 2;

    private const MAX_LENGTH = 50;

    public static function fromString(string $value): self
    {
        $normalized = mb_strtoupper(trim($value), 'UTF-8');

        return new self($normalized);
    }

    protected function validate(string $value): void
    {
        $len = mb_strlen($value);
        if ($len < self::MIN_LENGTH || $len > self::MAX_LENGTH) {
            throw new InvalidArgumentException(
                sprintf('El código de cupón debe tener entre %d y %d caracteres.', self::MIN_LENGTH, self::MAX_LENGTH)
            );
        }

        if (! preg_match('/^[A-Z0-9_-]+$/', $value)) {
            throw new InvalidArgumentException('El código del cupón solo puede contener letras mayúsculas, números, guiones y guiones bajos.');
        }
    }
}
