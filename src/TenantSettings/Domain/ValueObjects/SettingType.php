<?php

declare(strict_types=1);

namespace Src\TenantSettings\Domain\ValueObjects;

use InvalidArgumentException;

final class SettingType
{
    public const STRING = 'string';

    public const BOOLEAN = 'boolean';

    public const JSON = 'json';

    public const INTEGER = 'integer';

    public const FLOAT = 'float';

    private const VALID_TYPES = [
        self::STRING,
        self::BOOLEAN,
        self::JSON,
        self::INTEGER,
        self::FLOAT,
    ];

    private string $value;

    public function __construct(string $value = self::STRING)
    {
        $normalized = strtolower(trim($value));
        if (! in_array($normalized, self::VALID_TYPES, true)) {
            throw new InvalidArgumentException("El tipo de configuración '{$value}' es inválido. Valores permitidos: ".implode(', ', self::VALID_TYPES));
        }

        $this->value = $normalized;
    }

    public static function string(): self
    {
        return new self(self::STRING);
    }

    public static function boolean(): self
    {
        return new self(self::BOOLEAN);
    }

    public static function json(): self
    {
        return new self(self::JSON);
    }

    public static function integer(): self
    {
        return new self(self::INTEGER);
    }

    public static function float(): self
    {
        return new self(self::FLOAT);
    }

    public static function fromString(string $type): self
    {
        return new self($type);
    }

    public function value(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value();
    }

    public function castValue(?string $raw): mixed
    {
        if ($raw === null) {
            return null;
        }

        return match ($this->value) {
            self::BOOLEAN => filter_var($raw, FILTER_VALIDATE_BOOLEAN),
            self::INTEGER => (int) $raw,
            self::FLOAT => (float) $raw,
            self::JSON => json_decode($raw, true) ?? [],
            default => $raw,
        };
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
