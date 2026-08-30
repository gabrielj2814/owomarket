<?php

declare(strict_types=1);

namespace Src\Order\Domain\ValueObjects;

use InvalidArgumentException;

enum OrderStatus: string
{
    case PENDING = 'pending';
    case CONFIRMED = 'confirmed';
    case PROCESSING = 'processing';
    case SHIPPED = 'shipped';
    case DELIVERED = 'delivered';
    case CANCELLED = 'cancelled';
    case REFUNDED = 'refunded';

    public static function fromString(string $status): self
    {
        $normalized = strtolower(trim($status));

        return match ($normalized) {
            'pending' => self::PENDING,
            'confirmed' => self::CONFIRMED,
            'processing' => self::PROCESSING,
            'shipped' => self::SHIPPED,
            'delivered' => self::DELIVERED,
            'cancelled' => self::CANCELLED,
            'refunded' => self::REFUNDED,
            default => throw new InvalidArgumentException("Estado de orden inválido: '{$status}'."),
        };
    }

    public function isPending(): bool
    {
        return $this === self::PENDING;
    }

    public function isConfirmed(): bool
    {
        return $this === self::CONFIRMED;
    }

    public function isProcessing(): bool
    {
        return $this === self::PROCESSING;
    }

    public function isShipped(): bool
    {
        return $this === self::SHIPPED;
    }

    public function isDelivered(): bool
    {
        return $this === self::DELIVERED;
    }

    public function isCancelled(): bool
    {
        return $this === self::CANCELLED;
    }

    public function isRefunded(): bool
    {
        return $this === self::REFUNDED;
    }

    public function canBeConfirmed(): bool
    {
        return $this === self::PENDING;
    }

    public function canBeProcessed(): bool
    {
        return in_array($this, [self::PENDING, self::CONFIRMED], true);
    }

    public function canBeShipped(): bool
    {
        return in_array($this, [self::CONFIRMED, self::PROCESSING], true);
    }

    public function canBeDelivered(): bool
    {
        return $this === self::SHIPPED;
    }

    public function canBeCancelled(): bool
    {
        return in_array($this, [self::PENDING, self::CONFIRMED, self::PROCESSING], true);
    }

    public function canBeRefunded(): bool
    {
        return in_array($this, [self::CONFIRMED, self::PROCESSING, self::SHIPPED, self::DELIVERED], true);
    }

    /**
     * Hallazgo SH1: si este pedido admite que exista un envio suyo.
     *
     * Distinto de `canBeShipped()`, que responde si el pedido puede pasar a `shipped`
     * *ahora mismo*. Este dice si tiene sentido siquiera generar una guia:
     *
     * - `pending` no, porque despachar sin confirmar se salta el paso de confirmar.
     * - `cancelled` y `refunded` tampoco: el pedido esta cerrado, su stock ya volvio al
     *   inventario (N13) y su comision ya se revirtio (D2). Marcar entregado un envio suyo
     *   lo resucitaba a `delivered`, y las tres cosas no pueden ser ciertas a la vez.
     * - `shipped` y `delivered` si, porque ya tienen envios y un pedido admite varios.
     */
    public function acceptsShipments(): bool
    {
        return in_array($this, [self::CONFIRMED, self::PROCESSING, self::SHIPPED, self::DELIVERED], true);
    }
}
