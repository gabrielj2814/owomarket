<?php

declare(strict_types=1);

namespace Src\TenantSettings\Domain\ValueObjects;

use InvalidArgumentException;

final class SettingGroup
{
    public const GENERAL = 'general';

    public const APPEARANCE = 'appearance';

    public const SOCIAL = 'social';

    public const SEO = 'seo';

    public const NOTIFICATIONS = 'notifications';

    private const VALID_GROUPS = [
        self::GENERAL,
        self::APPEARANCE,
        self::SOCIAL,
        self::SEO,
        self::NOTIFICATIONS,
    ];

    private string $value;

    public function __construct(string $value = self::GENERAL)
    {
        $normalized = strtolower(trim($value));
        if (! in_array($normalized, self::VALID_GROUPS, true)) {
            throw new InvalidArgumentException("El grupo de configuración '{$value}' es inválido. Grupos permitidos: ".implode(', ', self::VALID_GROUPS));
        }

        $this->value = $normalized;
    }

    public static function general(): self
    {
        return new self(self::GENERAL);
    }

    public static function appearance(): self
    {
        return new self(self::APPEARANCE);
    }

    public static function social(): self
    {
        return new self(self::SOCIAL);
    }

    public static function seo(): self
    {
        return new self(self::SEO);
    }

    public static function notifications(): self
    {
        return new self(self::NOTIFICATIONS);
    }

    public static function fromString(string $group): self
    {
        return new self($group);
    }

    public function value(): string
    {
        return $this->value;
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
