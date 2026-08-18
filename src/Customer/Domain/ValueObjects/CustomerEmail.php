<?php

declare(strict_types=1);

namespace Src\Customer\Domain\ValueObjects;

use InvalidArgumentException;

final class CustomerEmail
{
    private function __construct(
        private readonly string $value
    ) {
        $trimmed = trim($this->value);
        if (empty($trimmed)) {
            throw new InvalidArgumentException('El correo del cliente no puede estar vacío.');
        }
        if (! filter_var($trimmed, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException("El correo '{$trimmed}' no tiene un formato válido.");
        }
    }

    public static function fromString(string $email): self
    {
        return new self(strtolower(trim($email)));
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
