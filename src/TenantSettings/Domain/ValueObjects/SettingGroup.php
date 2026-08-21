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

    /**
     * Datos de cobro del comercio (Pago Móvil, Binance Pay, transferencia).
     *
     * Añadido en la Fase 0.5 por el hallazgo G1: hasta entonces no existía
     * ningún sitio donde el comerciante configurara su banco, RIF, teléfono
     * o Binance Pay ID, y el checkout servía datos de demostración
     * hardcodeados — el comprador transfería a una cuenta que no era la de
     * la tienda.
     */
    public const PAYMENT = 'payment';

    private const VALID_GROUPS = [
        self::GENERAL,
        self::APPEARANCE,
        self::SOCIAL,
        self::SEO,
        self::NOTIFICATIONS,
        self::PAYMENT,
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

    public static function payment(): self
    {
        return new self(self::PAYMENT);
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
