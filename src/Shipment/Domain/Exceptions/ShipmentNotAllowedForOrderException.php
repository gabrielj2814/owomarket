<?php

declare(strict_types=1);

namespace Src\Shipment\Domain\Exceptions;

use DomainException;

/**
 * Hallazgo SH1: el pedido no está en condiciones de tener un envío.
 *
 * Lleva mensaje propio en vez de reutilizar `InvalidOrderStateTransitionException` porque
 * quien se lo come es el del almacén generando una guía, y "no se puede cambiar el estado
 * de la orden de 'pending' a 'shipped'" no le dice qué hacer. Aquí sí: confírmalo, o mira
 * por qué está anulado.
 */
final class ShipmentNotAllowedForOrderException extends DomainException
{
    public static function because(string $orderId, string $orderStatus): self
    {
        $motivo = match ($orderStatus) {
            'pending' => 'está sin confirmar. Confirma el pedido antes de generar la guía de despacho.',
            'cancelled' => 'está cancelado, y su stock ya volvió al inventario.',
            'refunded' => 'está reembolsado.',
            default => "está en '{$orderStatus}' y no admite envíos.",
        };

        return new self("El pedido '{$orderId}' {$motivo}");
    }
}
