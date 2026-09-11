<?php

declare(strict_types=1);

namespace Src\Monetization\Application\UseCases;

use Illuminate\Support\Facades\Log;
use Src\Monetization\Infrastructure\Eloquent\Models\PlatformCommission;
use Src\Payment\Infrastructure\Eloquent\Models\CentralSetting;
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
 * | `Release`  | ¿Llegó la mercancía? | El comprador confirma, o vence el plazo (subsistema 3) |
 *
 * **Subsistema 4:** liberar ya no es todo o nada. Al liberar se aparta una reserva —un
 * porcentaje de la parte del comerciante— que sigue retenida un tiempo más. Ese colchón es lo
 * que paga una reclamación posterior sin que la plataforma tenga que perseguir a nadie, y es
 * lo que convierte el hueco 2 en imposible por construcción en vez de gestionable.
 *
 * Clase no `final`: los tests la sustituyen por un doble de Mockery (ver `reglas.md`).
 */
class ReleaseOrderCommissionUseCase
{
    /**
     * Porcentaje de la parte del comerciante que queda retenido como fondo de garantía.
     *
     * Diez por ciento: conservador sin asfixiar. Y conservador a propósito, por una asimetría
     * que decide el asunto — bajar una retención después es un regalo que el comerciante
     * celebra; subirla, al descubrir a los seis meses que el número se quedó corto, se vive
     * como una traición y es una discusión con cada tienda.
     */
    private const PORCENTAJE_POR_DEFECTO = 10.0;

    /** Días que la reserva sigue retenida después de liberarse el resto de la venta. */
    private const DIAS_POR_DEFECTO = 60;

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
            $comisiones = PlatformCommission::where('order_id', $orderId)
                ->whereNull('released_at')
                ->get();

            if ($comisiones->isEmpty()) {
                return 0;
            }

            $porcentaje = $this->porcentajeDeReserva();
            $dias = $this->diasDeReserva();
            $ahora = now();

            foreach ($comisiones as $comision) {
                $parteDelComerciante = (float) $comision->order_total - (float) $comision->commission_amount;

                // Una nota de crédito tiene la parte del comerciante en negativo. Retener un
                // porcentaje de una deuda no significa nada --y restaría al revés en el
                // saldo--, así que sólo se reserva sobre ventas reales.
                $reserva = $parteDelComerciante > 0
                    ? round($parteDelComerciante * ($porcentaje / 100), 2)
                    : 0.0;

                $comision->released_at = $ahora;
                $comision->reserve_amount = $reserva;
                $comision->reserve_until = $reserva > 0 ? $ahora->copy()->addDays($dias) : null;
                $comision->save();
            }

            return $comisiones->count();
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

    /**
     * Configurable desde los ajustes de cobro, como el resto de las palancas de dinero.
     *
     * Cero es legítimo —significa no retener nada— pero el tope es 100: una reserva mayor que
     * la venta dejaría al comerciante con saldo negativo por vender.
     */
    private function porcentajeDeReserva(): float
    {
        $valor = $this->ajuste('central_guarantee_reserve_percent');

        if ($valor === null) {
            return self::PORCENTAJE_POR_DEFECTO;
        }

        return max(0.0, min(100.0, (float) $valor));
    }

    private function diasDeReserva(): int
    {
        $valor = $this->ajuste('central_guarantee_reserve_days');

        return $valor === null ? self::DIAS_POR_DEFECTO : max(0, (int) $valor);
    }

    private function ajuste(string $clave): ?string
    {
        try {
            $valor = CentralSetting::query()
                ->where('group', 'payment')
                ->where('key', $clave)
                ->value('value');
        } catch (Throwable) {
            return null;
        }

        return $valor === null || trim((string) $valor) === '' ? null : (string) $valor;
    }
}
