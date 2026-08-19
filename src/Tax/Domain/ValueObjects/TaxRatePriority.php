<?php

declare(strict_types=1);

namespace Src\Tax\Domain\ValueObjects;

use Src\Shared\Domain\ValueObjects\IntValueObject;

final class TaxRatePriority extends IntValueObject
{
    public static function fromInt(int $value): self
    {
        return new self($value);
    }

    protected function validate(int $value): void
    {
        // No restriction or must be >= 0
        if ($value < 0) {
            throw new \InvalidArgumentException('La prioridad no puede ser negativa.');
        }
    }
}
