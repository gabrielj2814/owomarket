<?php

declare(strict_types=1);

namespace Src\Tax\Domain\ValueObjects;

use InvalidArgumentException;
use Src\Shared\Domain\ValueObjects\StringValueObject;

final class TaxRateName extends StringValueObject
{
    public static function make(string $value): self
    {
        return new self($value);
    }

    protected function validate(string $value): void
    {
        $trimmed = trim($value);

        if (mb_strlen($trimmed) < 2 || mb_strlen($trimmed) > 150) {
            throw new InvalidArgumentException('El nombre del impuesto debe tener entre 2 y 150 caracteres.');
        }
    }
}
