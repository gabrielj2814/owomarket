<?php

declare(strict_types=1);

namespace Src\ExchangeRate\Domain\ValueObjects;

use DateTimeImmutable;
use InvalidArgumentException;

final class RateDate
{
    private DateTimeImmutable $value;

    public function __construct(DateTimeImmutable|string $value)
    {
        if (is_string($value)) {
            $parsed = DateTimeImmutable::createFromFormat('Y-m-d', trim($value))
                ?: DateTimeImmutable::createFromFormat('Y-m-d H:i:s', trim($value))
                ?: date_create_immutable($value);

            if ($parsed === false) {
                throw new InvalidArgumentException("Formato de fecha de tasa inválido: '{$value}'", 400);
            }

            $this->value = $parsed->setTime(0, 0, 0);
        } else {
            $this->value = $value->setTime(0, 0, 0);
        }
    }

    public static function make(DateTimeImmutable|string $value): self
    {
        return new self($value);
    }

    public static function today(): self
    {
        return new self(new DateTimeImmutable);
    }

    public function value(): string
    {
        return $this->value->format('Y-m-d');
    }

    public function toDateTime(): DateTimeImmutable
    {
        return $this->value;
    }

    public function format(string $format = 'd/m/Y'): string
    {
        return $this->value->format($format);
    }

    public function equals(self $other): bool
    {
        return $this->value() === $other->value();
    }

    public function __toString(): string
    {
        return $this->value();
    }
}
