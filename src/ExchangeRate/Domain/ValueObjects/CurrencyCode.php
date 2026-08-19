<?php

declare(strict_types=1);

namespace Src\ExchangeRate\Domain\ValueObjects;

use InvalidArgumentException;

final class CurrencyCode
{
    public const USD = 'USD';

    public const VES = 'VES';

    public const USDT = 'USDT';

    public const USDC = 'USDC';

    public const EUR = 'EUR';

    private const ALLOWED = [
        self::USD,
        self::VES,
        self::USDT,
        self::USDC,
        self::EUR,
    ];

    private string $value;

    public function __construct(string $value)
    {
        $normalized = strtoupper(trim($value));

        if (! in_array($normalized, self::ALLOWED, true)) {
            throw new InvalidArgumentException("Código de moneda no válido: '{$value}'", 400);
        }

        $this->value = $normalized;
    }

    public static function make(string $value): self
    {
        return new self($value);
    }

    public static function usd(): self
    {
        return new self(self::USD);
    }

    public static function ves(): self
    {
        return new self(self::VES);
    }

    public static function usdt(): self
    {
        return new self(self::USDT);
    }

    public static function usdc(): self
    {
        return new self(self::USDC);
    }

    public function value(): string
    {
        return $this->value;
    }

    public function isUsd(): bool
    {
        return $this->value === self::USD;
    }

    public function isVes(): bool
    {
        return $this->value === self::VES;
    }

    public function isCrypto(): bool
    {
        return $this->value === self::USDT || $this->value === self::USDC;
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
