<?php

declare(strict_types=1);

namespace Src\Shipment\Domain\ValueObjects;

use InvalidArgumentException;

final class ShipmentCost
{
    private float $amount;

    public function __construct(float $amount)
    {
        if ($amount < 0) {
            throw new InvalidArgumentException('El costo de envío no puede ser negativo.');
        }

        $this->amount = round($amount, 2);
    }

    public static function fromFloat(float $amount): self
    {
        return new self($amount);
    }

    public static function zero(): self
    {
        return new self(0.0);
    }

    public function amount(): float
    {
        return $this->amount;
    }

    public function equals(self $other): bool
    {
        return abs($this->amount - $other->amount()) < 0.0001;
    }

    public function __toString(): string
    {
        return number_format($this->amount, 2, '.', '');
    }
}
