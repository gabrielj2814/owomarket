<?php

namespace Src\Shared\ValuesObjects;

use DateTimeImmutable;
use DateTimeInterface;
use InvalidArgumentException;

final class CreatedAt
{
    private DateTimeImmutable $value;

    public function __construct(?DateTimeImmutable $value = null)
    {
        $this->value = $value ?? new DateTimeImmutable();
    }

    public static function fromString(string $value): self
    {
        $date = DateTimeImmutable::createFromFormat(DateTimeInterface::ATOM, $value);

        if ($date === false) {
            $date = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $value);
        }

        if ($date === false) {
            throw new InvalidArgumentException("Formato de fecha de creación inválido: {$value}");
        }

        return new self($date);
    }

    public static function now(): self
    {
        return new self(new DateTimeImmutable());
    }

    public function value(): DateTimeImmutable
    {
        return $this->value;
    }

    public function toString(): string
    {
        return $this->value->format('Y-m-d H:i:s');
    }

    public function toIso8601(): string
    {
        return $this->value->format(DateTimeInterface::ATOM);
    }

    public function isFuture(): bool
    {
        return $this->value > new DateTimeImmutable();
    }

    public function isPast(): bool
    {
        return $this->value < new DateTimeImmutable();
    }

    public function diffInSeconds(DateTimeImmutable $other): int
    {
        return abs($this->value->getTimestamp() - $other->getTimestamp());
    }

    public function isOlderThan(int $minutes): bool
    {
        $now = new DateTimeImmutable();
        $diff = $now->getTimestamp() - $this->value->getTimestamp();

        return $diff > ($minutes * 60);
    }

    public function isNewerThan(int $minutes): bool
    {
        return !$this->isOlderThan($minutes);
    }

    public function equals(self $other): bool
    {
        return $this->value->getTimestamp() === $other->value->getTimestamp();
    }
}
