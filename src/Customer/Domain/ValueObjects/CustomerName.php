<?php

declare(strict_types=1);

namespace Src\Customer\Domain\ValueObjects;

use InvalidArgumentException;

final class CustomerName
{
    private function __construct(
        private readonly string $value
    ) {
        $trimmed = trim($this->value);
        if (empty($trimmed)) {
            throw new InvalidArgumentException('El nombre del cliente no puede estar vacío.');
        }
        if (mb_strlen($trimmed) < 2) {
            throw new InvalidArgumentException('El nombre del cliente debe tener al menos 2 caracteres.');
        }
        if (mb_strlen($trimmed) > 255) {
            throw new InvalidArgumentException('El nombre del cliente no puede exceder 255 caracteres.');
        }
    }

    public static function fromString(string $name): self
    {
        return new self(trim($name));
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
