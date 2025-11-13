<?php

namespace Src\Shared\ValuesObjects;

use DateTimeImmutable;
use DateTimeInterface;
use InvalidArgumentException;

final class SoftDeleteAt
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
            throw new InvalidArgumentException("Formato de fecha de soft delete inválido: {$value}");
        }

        return new self($date);
    }

    public static function now(): self
    {
        return new self(new DateTimeImmutable());
    }

    public function markAsDeleted(): self
    {
        return new self(new DateTimeImmutable());
    }

    public function restore(): self
    {
        return new self();
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

    public function isDeleted(): bool
    {
        return $this->value !== null;
    }

    public function isNotDeleted(): bool
    {
        return $this->value === null;
    }

    public function wasDeletedRecently(int $minutes = 5): bool
    {
        if ($this->value === null) {
            return false;
        }

        $now = new DateTimeImmutable();
        $diff = $now->getTimestamp() - $this->value->getTimestamp();

        return $diff <= ($minutes * 60);
    }

    public function isEligibleForPermanentDeletion(int $days = 30): bool
    {
        if ($this->value === null) {
            return false;
        }

        $now = new DateTimeImmutable();
        $diff = $now->getTimestamp() - $this->value->getTimestamp();

        return $diff > ($days * 24 * 60 * 60); // Más de X días
    }

    public function daysSinceDeletion(): ?int
    {
        if ($this->value === null) {
            return null;
        }

        $now = new DateTimeImmutable();
        $diff = $now->getTimestamp() - $this->value->getTimestamp();

        return (int) floor($diff / (24 * 60 * 60));
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
