<?php

declare(strict_types=1);

namespace Src\Category\Domain\ValueObjects;

use InvalidArgumentException;

final class ParentCategoryId
{
    private function __construct(private readonly ?int $value)
    {
        if ($this->value !== null && $this->value <= 0) {
            throw new InvalidArgumentException('Parent Category ID must be a positive integer.');
        }
    }

    public static function fromNullableInt(?int $value): self
    {
        return new self($value);
    }

    public static function null(): self
    {
        return new self(null);
    }

    public function value(): ?int
    {
        return $this->value;
    }

    public function isNull(): bool
    {
        return $this->value === null;
    }
}
