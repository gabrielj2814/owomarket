<?php

namespace Src\Shared\ValuesObjects;

use DateTimeImmutable;
use DateTimeInterface;
use InvalidArgumentException;

final class UpdatedAt
{
    private ?DateTimeImmutable $value;

    public function __construct(?DateTimeImmutable $value = null)
    {
        $this->value = $value;
    }

    public static function fromString(?string $value): self
    {
        if ($value === null) {
            return new self();
        }

        $date = DateTimeImmutable::createFromFormat(DateTimeInterface::ATOM, $value);

        if ($date === false) {
            $date = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $value);
        }

        if ($date === false) {
            throw new InvalidArgumentException("Formato de fecha de actualización inválido: {$value}");
        }

        return new self($date);
    }

    public static function now(): self
    {
        return new self(new DateTimeImmutable());
    }

    public function touch(): self
    {
        return new self(new DateTimeImmutable());
    }

    public function value(): ?DateTimeImmutable
    {
        return $this->value;
    }

    public function toString(): ?string
    {
        return $this->value?->format('Y-m-d H:i:s');
    }

    public function toIso8601(): ?string
    {
        return $this->value?->format(DateTimeInterface::ATOM);
    }

    public function isNull(): bool
    {
        return $this->value === null;
    }

    public function isNotNull(): bool
    {
        return $this->value !== null;
    }

    public function isNewerThan(CreatedAt $createdAt, int $minutes = 1): bool
    {
        if ($this->value === null) {
            return false;
        }

        $diff = $this->value->getTimestamp() - $createdAt->value()->getTimestamp();
        return $diff > ($minutes * 60);
    }

    public function wasRecentlyUpdated(int $minutes = 5): bool
    {
        if ($this->value === null) {
            return false;
        }

        $now = new DateTimeImmutable();
        $diff = $now->getTimestamp() - $this->value->getTimestamp();

        return $diff <= ($minutes * 60);
    }

    public function equals(self $other): bool
    {
        if ($this->value === null && $other->value === null) {
            return true;
        }

        if ($this->value === null || $other->value === null) {
            return false;
        }

        return $this->value->getTimestamp() === $other->value->getTimestamp();
    }
}
