<?php

declare(strict_types=1);

namespace Src\Billing\Domain\ValueObjects;

use InvalidArgumentException;

final class BillingEmail
{
    private function __construct(
        private readonly string $value
    ) {
        $trimmed = trim($this->value);
        if (empty($trimmed)) {
            throw new InvalidArgumentException('El correo de facturación no puede estar vacío.');
        }
        if (! filter_var($trimmed, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException("El formato del correo electrónico '{$trimmed}' no es válido.");
        }
    }

    public static function fromString(string $email): self
    {
        return new self($email);
    }

    public function value(): string
    {
        return strtolower(trim($this->value));
    }

    public function equals(self $other): bool
    {
        return $this->value() === $other->value();
    }
}
