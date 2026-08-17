<?php

declare(strict_types=1);

namespace Src\Shipping\Domain\ValueObjects;

use InvalidArgumentException;
use Src\Shared\Domain\ValueObjects\StringValueObject;

final class ShippingRateType extends StringValueObject
{
    public const TYPE_FLAT = 'flat';

    public const TYPE_FREE = 'free';

    public const TYPE_PRICE_BASED = 'price_based';

    public const TYPE_WEIGHT_BASED = 'weight_based';

    public const ALLOWED_TYPES = [
        self::TYPE_FLAT,
        self::TYPE_FREE,
        self::TYPE_PRICE_BASED,
        self::TYPE_WEIGHT_BASED,
    ];

    public static function fromString(string $value): self
    {
        return new self($value);
    }

    public static function flat(): self
    {
        return new self(self::TYPE_FLAT);
    }

    public static function free(): self
    {
        return new self(self::TYPE_FREE);
    }

    public static function priceBased(): self
    {
        return new self(self::TYPE_PRICE_BASED);
    }

    public static function weightBased(): self
    {
        return new self(self::TYPE_WEIGHT_BASED);
    }

    public function isFlat(): bool
    {
        return $this->value === self::TYPE_FLAT;
    }

    public function isFree(): bool
    {
        return $this->value === self::TYPE_FREE;
    }

    public function isPriceBased(): bool
    {
        return $this->value === self::TYPE_PRICE_BASED;
    }

    public function isWeightBased(): bool
    {
        return $this->value === self::TYPE_WEIGHT_BASED;
    }

    protected function validate(string $value): void
    {
        $normalized = mb_strtolower(trim($value));

        if (! in_array($normalized, self::ALLOWED_TYPES, true)) {
            throw new InvalidArgumentException(
                sprintf('Tipo de tarifa no válido: "%s". Tipos permitidos: %s', $value, implode(', ', self::ALLOWED_TYPES))
            );
        }
    }
}
