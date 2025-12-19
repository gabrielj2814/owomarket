<?php

namespace Src\User\Domain\ValueObjects;

use DateTimeImmutable;
use InvalidArgumentException;

final class EmailVerifiedAt
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

        $date = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $value);
        if ($date === false) {
            throw new InvalidArgumentException("Formato de fecha de verificación inválido", 400);
        }

        return new self($date);
    }

    public function verify(): self
    {
        return new self(new DateTimeImmutable());
    }

    public function isVerified(): bool
    {
        return $this->value !== null;
    }

    public function isRecentlyVerified(int $minutes = 5): bool
    {
        if ($this->value === null) {
            return false;
        }

        $now = new DateTimeImmutable();
        $diff = $now->getTimestamp() - $this->value->getTimestamp();

        return $diff <= ($minutes * 60);
    }

    public function value(): ?DateTimeImmutable
    {
        return $this->value;
    }

    public function toString(): ?string
    {
        return $this->value?->format('Y-m-d H:i:s');
    }

    public function equals(self $other): bool
    {
        return $this->toString() === $other->toString();
    }
}
