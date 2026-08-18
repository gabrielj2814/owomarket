<?php

declare(strict_types=1);

namespace Src\Shipment\Domain\ValueObjects;

use InvalidArgumentException;

final class TrackingNumber
{
    private string $value;

    public function __construct(string $value)
    {
        $trimmed = trim($value);
        if (empty($trimmed)) {
            throw new InvalidArgumentException('El número de seguimiento no puede estar vacío.');
        }

        if (strlen($trimmed) < 3 || strlen($trimmed) > 100) {
            throw new InvalidArgumentException('El número de seguimiento debe tener entre 3 y 100 caracteres.');
        }

        $this->value = $trimmed;
    }

    public static function fromString(string $value): self
    {
        return new self($value);
    }

    public function value(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value();
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
