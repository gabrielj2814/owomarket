<?php

declare(strict_types=1);

namespace Src\Review\Domain\ValueObjects;

use Src\Review\Domain\Exceptions\InvalidRatingException;

final class Rating
{
    private int $value;

    public function __construct(int $value)
    {
        if ($value < 1 || $value > 5) {
            throw InvalidRatingException::forValue($value);
        }

        $this->value = $value;
    }

    public static function fromInt(int $value): self
    {
        return new self($value);
    }

    public function value(): int
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value();
    }

    public function isPositive(): bool
    {
        return $this->value >= 4;
    }

    public function isNeutral(): bool
    {
        return $this->value === 3;
    }

    public function isNegative(): bool
    {
        return $this->value <= 2;
    }

    public function __toString(): string
    {
        return (string) $this->value;
    }
}
