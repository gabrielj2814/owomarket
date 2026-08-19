<?php

declare(strict_types=1);

namespace Src\Category\Domain\ValueObjects;

final class CategoryDescription
{
    private function __construct(private readonly ?string $value) {}

    public static function make(?string $value): self
    {
        return new self($value !== null ? trim($value) : null);
    }

    public function value(): ?string
    {
        return $this->value;
    }

    public function isNull(): bool
    {
        return $this->value === null;
    }
}
