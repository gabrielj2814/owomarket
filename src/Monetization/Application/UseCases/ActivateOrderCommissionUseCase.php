<?php

declare(strict_types=1);

namespace Src\Monetization\Application\UseCases;

use Illuminate\Support\Facades\Log;
use Src\Monetization\Infrastructure\Eloquent\Models\PlatformCommission;
use Throwable;

/**
 * Convierte en cobrable la comisión de un pedido cuyo pago se ha confirmado (hallazgo N15).
 *
 * La comisión nace en `awaiting_payment`: devengada, visible, pero fuera de las
 * liquidaciones. Este caso de uso es el único que la pasa a `pending`.
 *
 * Antes nacía directamente en `pending` sin mirar el `payment_status` —que para pago móvil,
 * transferencia manual y contra entrega es siempre `pending`—, así que **a la tienda se le
 * cobraba comisión por ventas que nunca llegó a cobrar**. La Fase 1.2 (hallazgo D2) tapó
 * el síntoma reviertiendo al cancelar, pero eso dependía de que alguien cancelara: si el
 * cliente simplemente no pagaba y nadie tocaba el pedido, la comisión se liquidaba igual.
 *
 * Clase no `final`: los tests la sustituyen por un doble de Mockery (ver `reglas.md`).
 */
class ActivateOrderCommissionUseCase
{
    /**
     * @return int Comisiones que pasaron a cobrables.
     */
    public function execute(string $orderId): int
    {
        try {
            // Sólo se promueven las que siguen esperando pago. Una ya `collected`,
            // `waived` o `refunded` no vuelve atrás por un cambio de estado de pago.
            return PlatformCommission::where('order_id', $orderId)
                ->where('status', 'awaiting_payment')
                ->update(['status' => 'pending']);
        } catch (Throwable $e) {
            // No romper el cobro del pedido por un fallo en la base central, pero dejar
            // rastro: una comisión que se queda en `awaiting_payment` no se cobra nunca.
            Log::error('No se pudo activar la comisión de un pedido pagado', [
                'order_id' => $orderId,
                'error' => $e->getMessage(),
            ]);

            return 0;
        }
    }
}
