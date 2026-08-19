<?php

declare(strict_types=1);

namespace Src\Category\Domain\ValueObjects;

use InvalidArgumentException;
use Src\Shared\Domain\ValueObjects\StringValueObject;

final class CategoryName extends StringValueObject
{
    public static function make(string $value): self
    {
        return new self($value);
    }

    protected function validate(string $value): void
    {
        $trimmed = trim($value);
        if (mb_strlen($trimmed) < 2) {
            throw new InvalidArgumentException('Category name must have at least 2 characters.');
        }

        if (mb_strlen($trimmed) > 150) {
            throw new InvalidArgumentException('Category name cannot exceed 150 characters.');
        }
    }
}
