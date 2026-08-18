<?php

declare(strict_types=1);

namespace Src\Customer\Domain\ValueObjects;

use DateTimeImmutable;
use InvalidArgumentException;

final class BirthDate
{
    private function __construct(
        private readonly string $value
    ) {
        $trimmed = trim($this->value);
        $dt = DateTimeImmutable::createFromFormat('Y-m-d', $trimmed);
        if (! $dt || $dt->format('Y-m-d') !== $trimmed) {
            throw new InvalidArgumentException("La fecha de nacimiento '{$trimmed}' debe tener formato YYYY-MM-DD.");
        }
        $today = new DateTimeImmutable('today');
        if ($dt > $today) {
            throw new InvalidArgumentException('La fecha de nacimiento no puede ser futura.');
        }
    }

    public static function fromString(string $date): self
    {
        return new self(trim($date));
    }

    public static function nullable(?string $date): ?self
    {
        if ($date === null || trim($date) === '') {
            return null;
        }

        return new self(trim($date));
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
