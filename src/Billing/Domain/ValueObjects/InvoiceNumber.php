<?php

declare(strict_types=1);

namespace Src\Billing\Domain\ValueObjects;

use InvalidArgumentException;

final class InvoiceNumber
{
    private function __construct(
        private readonly string $value
    ) {
        $trimmed = trim($this->value);
        if (empty($trimmed)) {
            throw new InvalidArgumentException('El número de factura no puede estar vacío.');
        }
    }

    public static function fromString(string $number): self
    {
        return new self(strtoupper(trim($number)));
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
