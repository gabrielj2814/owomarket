<?php

declare(strict_types=1);

namespace Src\Customer\Domain\ValueObjects;

use Illuminate\Support\Str;
use InvalidArgumentException;

final class CustomerId
{
    private function __construct(
        private readonly string $value
    ) {
        if (empty(trim($this->value))) {
            throw new InvalidArgumentException('El ID del cliente no puede estar vacío.');
        }
    }

    public static function random(): self
    {
        return new self((string) Str::uuid());
    }

    public static function fromString(string $id): self
    {
        return new self($id);
    }

    public function value(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value();
    }
}
