<?php

declare(strict_types=1);

namespace Src\Attribute\Domain\ValueObjects;

use InvalidArgumentException;
use Src\Shared\Domain\ValueObjects\StringValueObject;

final class AttributeName extends StringValueObject
{
    private const MIN_LENGTH = 2;

    private const MAX_LENGTH = 150;

    public static function make(string $value): self
    {
        return new self($value);
    }

    protected function validate(string $value): void
    {
        $trimmed = trim($value);

        if (mb_strlen($trimmed) < self::MIN_LENGTH || mb_strlen($trimmed) > self::MAX_LENGTH) {
            throw new InvalidArgumentException(
                sprintf('El nombre del atributo debe tener entre %d y %d caracteres.', self::MIN_LENGTH, self::MAX_LENGTH)
            );
        }
    }
}
