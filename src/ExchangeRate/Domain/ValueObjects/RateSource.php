<?php

declare(strict_types=1);

namespace Src\ExchangeRate\Domain\ValueObjects;

use InvalidArgumentException;

final class RateSource
{
    public const BCV_SCRAPING = 'BCV_SCRAPING';

    public const MANUAL_ADMIN = 'MANUAL_ADMIN';

    public const API_FALLBACK = 'API_FALLBACK';

    private const ALLOWED = [
        self::BCV_SCRAPING,
        self::MANUAL_ADMIN,
        self::API_FALLBACK,
    ];

    private string $value;

    public function __construct(string $value)
    {
        $normalized = strtoupper(trim($value));

        if (! in_array($normalized, self::ALLOWED, true)) {
            throw new InvalidArgumentException("Origen de tasa de cambio no válido: '{$value}'", 400);
        }

        $this->value = $normalized;
    }

    public static function make(string $value): self
    {
        return new self($value);
    }

    public static function bcv(): self
    {
        return new self(self::BCV_SCRAPING);
    }

    public static function manual(): self
    {
        return new self(self::MANUAL_ADMIN);
    }

    public static function fallback(): self
    {
        return new self(self::API_FALLBACK);
    }

    public function value(): string
    {
        return $this->value;
    }

    public function isBcv(): bool
    {
        return $this->value === self::BCV_SCRAPING;
    }

    public function isManual(): bool
    {
        return $this->value === self::MANUAL_ADMIN;
    }

    public function isFallback(): bool
    {
        return $this->value === self::API_FALLBACK;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value();
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
