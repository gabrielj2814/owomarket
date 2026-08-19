<?php

declare(strict_types=1);

namespace Src\Order\Domain\ValueObjects;

use InvalidArgumentException;

final class Money
{
    private float $amount;

    public function __construct(float|int $amount)
    {
        $floatAmount = (float) $amount;
        if ($floatAmount < 0) {
            throw new InvalidArgumentException("El monto monetario no puede ser negativo: {$floatAmount}.");
        }

        $this->amount = round($floatAmount, 2);
    }

    public static function zero(): self
    {
        return new self(0.0);
    }

    public static function from(float|int $amount): self
    {
        return new self($amount);
    }

    public function amount(): float
    {
        return $this->amount;
    }

    public function add(self $other): self
    {
        return new self($this->amount + $other->amount());
    }

    public function subtract(self $other): self
    {
        $newAmount = max(0.0, $this->amount - $other->amount());

        return new self($newAmount);
    }

    public function multiply(int|float $multiplier): self
    {
        return new self(max(0.0, $this->amount * $multiplier));
    }

    public function equals(self $other): bool
    {
        return abs($this->amount - $other->amount()) < 0.001;
    }

    public function isGreaterThan(self $other): bool
    {
        return $this->amount > $other->amount();
    }

    public function isZero(): bool
    {
        return $this->amount == 0.0;
    }

    public function formatted(string $currency = '$'): string
    {
        return sprintf('%s %s', $currency, number_format($this->amount, 2, '.', ','));
    }

    public function __toString(): string
    {
        return number_format($this->amount, 2, '.', '');
    }
}
