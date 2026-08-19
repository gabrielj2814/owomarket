<?php

declare(strict_types=1);

namespace Src\Payment\Domain\ValueObjects;

use InvalidArgumentException;

final class PaymentMethod
{
    private function __construct(
        private readonly string $value
    ) {
        $trimmed = trim($this->value);
        if (empty($trimmed)) {
            throw new InvalidArgumentException('El método de pago no puede estar vacío.');
        }
    }

    public static function fromString(string $method): self
    {
        return new self(strtolower(trim($method)));
    }

    public function value(): string
    {
        return $this->value;
    }

    public function isManual(): bool
    {
        return in_array($this->value, ['manual', 'manual_transfer', 'cash', 'cash_on_delivery', 'pos'], true);
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value();
    }
}
