<?php

declare(strict_types=1);

namespace Src\Customer\Domain\ValueObjects;

use InvalidArgumentException;

final class AddressType
{
    public const SHIPPING = 'shipping';

    public const BILLING = 'billing';

    public const BOTH = 'both';

    public const OTHER = 'other';

    private const VALID_TYPES = [
        self::SHIPPING,
        self::BILLING,
        self::BOTH,
        self::OTHER,
    ];

    private function __construct(
        private readonly string $value
    ) {
        if (! in_array($this->value, self::VALID_TYPES, true)) {
            throw new InvalidArgumentException("El tipo de dirección '{$this->value}' no es válido.");
        }
    }

    public static function shipping(): self
    {
        return new self(self::SHIPPING);
    }

    public static function billing(): self
    {
        return new self(self::BILLING);
    }

    public static function fromString(string $type): self
    {
        return new self(strtolower(trim($type)));
    }

    public function value(): string
    {
        return $this->value;
    }

    public function isShipping(): bool
    {
        return in_array($this->value, [self::SHIPPING, self::BOTH], true);
    }

    public function isBilling(): bool
    {
        return in_array($this->value, [self::BILLING, self::BOTH], true);
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value();
    }
}
