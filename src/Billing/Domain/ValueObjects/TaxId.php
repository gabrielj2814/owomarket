<?php

declare(strict_types=1);

namespace Src\Billing\Domain\ValueObjects;

use InvalidArgumentException;

final class TaxId
{
    private function __construct(
        private readonly string $value
    ) {
        $trimmed = trim($this->value);
        if (empty($trimmed)) {
            throw new InvalidArgumentException('El identificador fiscal (RUT/RFC/NIF/CIF/RUC) es obligatorio.');
        }
        if (strlen($trimmed) < 3 || strlen($trimmed) > 30) {
            throw new InvalidArgumentException('El identificador fiscal debe tener entre 3 y 30 caracteres.');
        }
    }

    public static function fromString(string $taxId): self
    {
        return new self($taxId);
    }

    public function value(): string
    {
        return strtoupper(trim($this->value));
    }

    public function equals(self $other): bool
    {
        return $this->value() === $other->value();
    }
}
