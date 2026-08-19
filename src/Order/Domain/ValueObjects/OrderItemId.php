<?php

declare(strict_types=1);

namespace Src\Order\Domain\ValueObjects;

use InvalidArgumentException;
use Ramsey\Uuid\Uuid;

final class OrderItemId
{
    private string $value;

    public function __construct(?string $value = null)
    {
        $val = $value ?? Uuid::uuid4()->toString();

        if (! Uuid::isValid($val)) {
            throw new InvalidArgumentException("El ID del ítem de la orden no es un UUID válido: '{$val}'.");
        }

        $this->value = $val;
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
