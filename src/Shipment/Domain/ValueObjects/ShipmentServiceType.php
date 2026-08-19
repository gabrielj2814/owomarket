<?php

declare(strict_types=1);

namespace Src\Shipment\Domain\ValueObjects;

use InvalidArgumentException;

final class ShipmentServiceType
{
    private string $value;

    public function __construct(string $value)
    {
        $trimmed = trim($value);
        if (empty($trimmed)) {
            throw new InvalidArgumentException('El tipo de servicio de envío no puede estar vacío.');
        }

        if (strlen($trimmed) < 2 || strlen($trimmed) > 100) {
            throw new InvalidArgumentException('El tipo de servicio debe tener entre 2 y 100 caracteres.');
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
        return strtolower($this->value) === strtolower($other->value());
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
