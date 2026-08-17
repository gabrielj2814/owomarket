<?php

declare(strict_types=1);

namespace Src\Brand\Domain\ValueObjects;

use InvalidArgumentException;
use Src\Shared\Domain\ValueObjects\IntValueObject;

final class BrandId extends IntValueObject
{
    public static function fromNullableInt(?int $value): ?self
    {
        return $value !== null ? new self($value) : null;
    }

    protected function validate(int $value): void
    {
        if ($value <= 0) {
            throw new InvalidArgumentException('El ID de la marca debe ser un entero positivo mayor que cero.');
        }
    }
}
