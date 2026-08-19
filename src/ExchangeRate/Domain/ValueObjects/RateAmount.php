<?php

declare(strict_types=1);

namespace Src\ExchangeRate\Domain\ValueObjects;

use InvalidArgumentException;

final class RateAmount
{
    private float $value;

    public function __construct(float|string|int $value)
    {
        if (is_string($value)) {
            $cleaned = str_replace(',', '.', trim($value));
            if (! is_numeric($cleaned)) {
                throw new InvalidArgumentException("El valor de la tasa de cambio debe ser numérico: '{$value}'", 400);
            }
            $value = (float) $cleaned;
        } else {
            $value = (float) $value;
        }

        if ($value <= 0) {
            throw new InvalidArgumentException('El valor de la tasa de cambio debe ser mayor a cero.', 400);
        }

        $this->value = round($value, 6);
    }

    public static function make(float|string|int $value): self
    {
        return new self($value);
    }

    public function value(): float
    {
        return $this->value;
    }

    public function format(int $decimals = 4, string $decPoint = ',', string $thousandsSep = '.'): string
    {
        return number_format($this->value, $decimals, $decPoint, $thousandsSep);
    }

    public function multiply(float $amount): float
    {
        return round($amount * $this->value, 2);
    }

    public function divide(float $amount): float
    {
        if ($this->value <= 0) {
            throw new InvalidArgumentException('División por cero en tasa de cambio.', 400);
        }

        return round($amount / $this->value, 2);
    }

    public function equals(self $other): bool
    {
        return abs($this->value - $other->value()) < 0.000001;
    }

    public function __toString(): string
    {
        return (string) $this->value;
    }
}
