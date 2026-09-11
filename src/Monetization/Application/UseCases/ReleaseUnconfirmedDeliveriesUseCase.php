<?php

declare(strict_types=1);

namespace Src\Monetization\Application\UseCases;

use Src\Monetization\Infrastructure\Eloquent\Models\OrderDeliveryConfirmation;
use Src\Payment\Infrastructure\Eloquent\Models\CentralSetting;
use Throwable;

/**
 * Libera las entregas que el comprador nunca confirmo.
 *
 * Sin esto, el subsistema 3 seria una trampa: un comprador que recibe su paquete y
 * simplemente no vuelve a entrar en la plataforma dejaria el dinero del comerciante
 * congelado para siempre. La confirmacion es una oportunidad para el comprador, no un
 * requisito que el comerciante deba mendigar.
 *
 * `released_by` distingue esta liberacion de la confirmada. No es contabilidad ociosa: es lo
 * unico que dira, cuando haya datos, que porcentaje de compradores confirma de verdad --y por
 * tanto si el plazo por defecto esta bien elegido--.
 */
final class ReleaseUnconfirmedDeliveriesUseCase
{
    /**
     * Dias que se espera la confirmacion del comprador antes de liberar igualmente.
     *
     * Siete por defecto: da margen real a quien viaja o tarda en abrir el paquete, sin
     * congelar el dinero del comerciante dos semanas --la queja numero uno de los vendedores
     * en cualquier marketplace--.
     */
    private const DIAS_POR_DEFECTO = 7;

    public function __construct(
        private readonly ReleaseOrderCommissionUseCase $release
    ) {}

    /**
     * @return int Entregas liberadas por vencimiento del plazo.
     */
    public function execute(): int
    {
        $limite = now()->subDays($this->diasDeEspera());

        $vencidas = OrderDeliveryConfirmation::whereNull('released_at')
            ->whereNotNull('declared_delivered_at')
            ->where('declared_delivered_at', '<=', $limite)
            ->get();

        $liberadas = 0;

        foreach ($vencidas as $expediente) {
            $expediente->released_by = 'timeout';
            $expediente->released_at = now();
            $expediente->save();

            $this->release->execute($expediente->order_id);
            $liberadas++;
        }

        return $liberadas;
    }

    /**
     * Configurable desde los ajustes de cobro, igual que `central_payout_hold_days`.
     *
     * Cero NO es valido aqui, a diferencia del plazo de garantia: liberar en el mismo
     * instante en que el comerciante declara la entrega es exactamente el agujero que este
     * subsistema viene a cerrar, solo que con un rodeo.
     */
    private function diasDeEspera(): int
    {
        try {
            $valor = CentralSetting::query()
                ->where('group', 'payment')
                ->where('key', 'central_delivery_confirmation_days')
                ->value('value');
        } catch (Throwable) {
            return self::DIAS_POR_DEFECTO;
        }

        if ($valor === null || trim((string) $valor) === '') {
            return self::DIAS_POR_DEFECTO;
        }

        return max(1, (int) $valor);
    }
}
