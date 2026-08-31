<?php

declare(strict_types=1);

namespace Src\Monetization\Application\UseCases;

use Illuminate\Support\Facades\Log;
use Src\Monetization\Infrastructure\Eloquent\Models\PlatformCommission;
use Throwable;

/**
 * Libera para retiro la comisión de un pedido entregado (Fase 4b del plan de wallet).
 *
 * Hasta la entrega, el importe de una venta central está en la wallet del comerciante pero
 * **no es retirable**. El motivo es el reembolso posterior al retiro: si la plataforma paga y
 * el comprador reclama después, el dinero ya salió de la cuenta y recuperarlo es perseguirlo.
 *
 * No confundir con `ActivateOrderCommissionUseCase`, que responde a otra pregunta. Son dos
 * condiciones distintas y hacen falta las dos:
 *
 * | Caso de uso | Pregunta | Lo dispara |
 * | :--- | :--- | :--- |
 * | `Activate` | ¿Entró el dinero? | Confirmar el cobro (N15) |
 * | `Release`  | ¿Llegó la mercancía? | El pedido pasa a `delivered` |
 *
 * Clase no `final`: los tests la sustituyen por un doble de Mockery (ver `reglas.md`).
 */
class ReleaseOrderCommissionUseCase
{
    /**
     * @param  string  $orderId  ID del pedido DE LA TIENDA, que es lo que guarda
     *                           `PlatformCommission.order_id`.
     * @return int Comisiones liberadas.
     */
    public function execute(string $orderId): int
    {
        try {
            // Sólo las que siguen retenidas. Volver a marcarlas movería la fecha de
            // liberación de una comisión ya liberada, y esa fecha es el rastro de cuándo
            // el dinero paso a ser reclamable.
            return PlatformCommission::where('order_id', $orderId)
                ->whereNull('released_at')
                ->update(['released_at' => now()]);
        } catch (Throwable $e) {
            // No romper la entrega por un fallo en la base central, pero dejar rastro: una
            // comision que se queda retenida es dinero que el comerciante no puede sacar.
            Log::error('No se pudo liberar la comisión de un pedido entregado.', [
                'order_id' => $orderId,
                'exception' => $e->getMessage(),
            ]);

            return 0;
        }
    }
}
