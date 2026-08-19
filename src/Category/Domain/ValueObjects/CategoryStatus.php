<?php

declare(strict_types=1);

namespace Src\Category\Domain\ValueObjects;

final class CategoryStatus
{
    private function __construct(private readonly bool $value) {}

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

    public function value(): bool
    {
        return $this->value;
    }

    public function isActive(): bool
    {
        return $this->value;
    }
}
