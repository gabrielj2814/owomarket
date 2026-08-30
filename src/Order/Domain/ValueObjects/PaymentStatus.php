<?php

declare(strict_types=1);

namespace Src\Order\Domain\ValueObjects;

use InvalidArgumentException;

enum PaymentStatus: string
{
    case PENDING = 'pending';
    case PAID = 'paid';
    case FAILED = 'failed';
    case REFUNDED = 'refunded';

    public static function fromString(string $status): self
    {
        $normalized = strtolower(trim($status));

        return match ($normalized) {
            'pending' => self::PENDING,
            'paid' => self::PAID,
            'failed' => self::FAILED,
            'refunded' => self::REFUNDED,
            default => throw new InvalidArgumentException("Estado de pago inválido: '{$status}'."),
        };
    }

    public function isPending(): bool
    {
        return $this === self::PENDING;
    }

    public function isPaid(): bool
    {
        return $this === self::PAID;
    }

    public function isFailed(): bool
    {
        return $this === self::FAILED;
    }

    public function isRefunded(): bool
    {
        return $this === self::REFUNDED;
    }

    // Hallazgo OR1: el estado del pago no tenia maquina de estados. `OrderStatus` si la
    // tiene, y sin estas guardas se alcanzaban combinaciones incoherentes -- un pedido
    // reembolsado podia volver a marcarse como pagado. Sin auto-transiciones, igual que
    // `OrderStatus::canBeConfirmed()`, que solo admite `pending`.

    /** Un pago fallido se puede reintentar; uno ya pagado o reembolsado, no. */
    public function canBePaid(): bool
    {
        return in_array($this, [self::PENDING, self::FAILED], true);
    }

    public function canBeFailed(): bool
    {
        return $this === self::PENDING;
    }

    /**
     * `pending` entra a proposito. Lo primero que se escribio aqui fue `PAID` a secas -- sin
     * pago no hay nada que devolver--, y el test de la comision (hallazgo D2) lo tumbo en el
     * acto: en pago movil, transferencia manual y contra entrega el `payment_status` es
     * `pending` para siempre, tal y como deja escrito N15, asi que un pedido entregado y
     * cobrado en mano se reembolsa desde `pending`. Bloquearlo habria roto el reembolso de
     * los metodos de pago mas usados del proyecto.
     *
     * Lo que si se frena: devolver un pago que fallo -- ahi nunca hubo dinero-- y reembolsar
     * dos veces.
     */
    public function canBeRefunded(): bool
    {
        return in_array($this, [self::PAID, self::PENDING], true);
    }
}
