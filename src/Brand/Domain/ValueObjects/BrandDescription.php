<?php

declare(strict_types=1);

namespace Src\Brand\Domain\ValueObjects;

final class BrandDescription
{
    public function __construct(private readonly ?string $value) {}

    public static function fromNullableString(?string $value): self
    {
        return new self($value !== null ? trim($value) : null);
    }

    public function value(): ?string
    {
        return $this->value;
    }
}
