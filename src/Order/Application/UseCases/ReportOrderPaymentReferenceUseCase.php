<?php

declare(strict_types=1);

namespace Src\Order\Application\UseCases;

use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Src\Monetization\Infrastructure\Eloquent\Models\PlatformCommission;
use Throwable;

/**
 * El comerciante reporta la referencia de pago que el comprador le pasó por otro canal.
 *
 * **Es una pista, no un hecho.** Confirmar que el dinero entró sigue siendo de la plataforma,
 * que es quien ve el extracto. Esto sólo le da al administrador con qué cuadrar un depósito
 * que de otro modo no sabría a qué pedido pertenece.
 *
 * Sustituye a lo que el comerciante podía hacer hasta la Fase 3b: marcar el pedido como pagado
 * él mismo, que desde que la plataforma cobra todas las ventas era autocertificarse un cobro
 * sobre una cuenta que no es la suya.
 *
 * **Escribe en la comisión y no en la fila de `payments` de la tienda** porque el que tiene que
 * leerlo es el administrador, y él consulta la base central. En `payments` quedaría escrito
 * donde nadie lo mira.
 */
final class ReportOrderPaymentReferenceUseCase
{
    public function execute(string $orderId, string $referencia, ?string $notas = null): PlatformCommission
    {
        $referencia = trim($referencia);

        if ($referencia === '') {
            throw new InvalidArgumentException('La referencia de pago no puede estar vacía.');
        }

        $comision = PlatformCommission::where('order_id', $orderId)->first();

        if (! $comision) {
            throw new InvalidArgumentException(
                'Este pedido no tiene un cobro pendiente que reportar.'
            );
        }

        $metadata = $comision->metadata ?? [];

        // Se guarda aparte de `payment_reference`, que es la que puso el comprador en el
        // checkout. Si no coinciden, eso es justo lo que el administrador necesita ver.
        $metadata['reported_reference'] = [
            'reference' => $referencia,
            'notes' => $notas,
            'reported_at' => now()->toIso8601String(),
        ];

        $comision->metadata = $metadata;
        $comision->save();

        try {
            Log::info('Referencia de pago reportada por el comerciante.', [
                'order_id' => $orderId,
                'tenant_id' => $comision->tenant_id,
            ]);
        } catch (Throwable) {
            // El rastro no puede tumbar el reporte.
        }

        return $comision;
    }
}
