<?php

declare(strict_types=1);

namespace Src\Product\Domain\ValueObjects;

use Src\Shared\Domain\ValueObjects\Uuid;

final class ProductId
{
    private function __construct(private readonly ?string $value) {}

    public static function fromString(string $value): self
    {
        Uuid::make($value);

        return new self($value);
    }

    public static function fromNullableString(?string $value): ?self
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        return self::fromString($value);
    }

    public static function null(): self
    {
        return new self(null);
    }

    public function value(): ?string
    {
        return $this->value;
    }

    public function isNull(): bool
    {
        return $this->value === null;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return (string) $this->value;
    }
}
