<?php

declare(strict_types=1);

namespace Src\Attribute\Domain\ValueObjects;

use InvalidArgumentException;
use Src\Shared\Domain\ValueObjects\StringValueObject;

final class AttributeValueText extends StringValueObject
{
    public static function make(string $value): self
    {
        return new self($value);
    }

    protected function validate(string $value): void
    {
        $trimmed = trim($value);

        if (mb_strlen($trimmed) < 1 || mb_strlen($trimmed) > 150) {
            throw new InvalidArgumentException('El valor del atributo debe tener entre 1 y 150 caracteres.');
        }
    }
}
