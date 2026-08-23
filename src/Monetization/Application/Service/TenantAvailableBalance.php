<?php

declare(strict_types=1);

namespace Src\Monetization\Application\Service;

use Illuminate\Support\Facades\Schema;
use Src\Monetization\Infrastructure\Eloquent\Models\CommissionSettlement;
use Src\Monetization\Infrastructure\Eloquent\Models\PlatformCommission;

/**
 * El saldo de una tienda, en un solo sitio (hallazgo T1).
 *
 * Esto vivia como metodo privado dentro de CreateTenantOwnerPayoutRequestUseCase, asi que
 * la aprobacion del retiro —que esta en el modulo Admin— no podia usarlo. El resultado fue
 * que el saldo se comprobaba al PEDIR y nunca mas: ApproveCentralPayoutRequestUseCase solo
 * miraba que la solicitud existiera, estuviera 'pending' y trajera referencia bancaria.
 *
 * Se extrae aqui en vez de copiarlo al modulo Admin a proposito: dos copias de una formula
 * de saldo que divergen es como se pierde dinero, y este repositorio ya ha demostrado que
 * las copias divergen.
 *
 * **Son dos preguntas distintas, y confundirlas rompe uno de los dos flujos.**
 */
final class TenantAvailableBalance
{
    /**
     * Cuanto puede PEDIR ahora el comerciante.
     *
     * Descuenta los retiros ya pagados y tambien los pendientes: si no, pedir dos veces
     * seguidas el saldo entero colaria, porque la primera solicitud aun no se ha pagado.
     */
    public function requestable(string $tenantId, bool $lock = false): float
    {
        $ganancias = $this->netEarnings($tenantId);
        $pagados = $this->payouts($tenantId, ['settled'], $lock);
        $pendientes = $this->payouts($tenantId, ['pending'], $lock);

        return max(0.0, $ganancias - $pagados - $pendientes);
    }

    /**
     * Cuanto se puede PAGAR ahora, sin contar los retiros pendientes.
     *
     * Aqui NO se descuentan los pendientes, y es deliberado: al aprobar hay que compararlo
     * contra el importe de esta solicitud. Si se descontaran, la propia solicitud que se
     * esta aprobando estaria restada de su propio respaldo y no se aprobaria nunca; y con
     * dos pendientes a la vez se rechazarian las dos en vez de pagar la primera.
     *
     * Al aprobar en orden, cada una pasa a 'settled' y reduce este saldo para la siguiente,
     * que es justo el comportamiento que se quiere.
     */
    public function settleable(string $tenantId, bool $lock = false): float
    {
        return max(0.0, $this->netEarnings($tenantId) - $this->payouts($tenantId, ['settled'], $lock));
    }

    private function netEarnings(string $tenantId): float
    {
        if (! Schema::hasTable('platform_commissions')) {
            return 0.0;
        }

        $ventas = (float) PlatformCommission::where('tenant_id', $tenantId)->sum('order_total');
        $comisiones = (float) PlatformCommission::where('tenant_id', $tenantId)->sum('commission_amount');

        return max(0.0, $ventas - $comisiones);
    }

    /**
     * @param  array<int, string>  $estados
     */
    private function payouts(string $tenantId, array $estados, bool $lock): float
    {
        if (! Schema::hasTable('commission_settlements')) {
            return 0.0;
        }

        $consulta = CommissionSettlement::where('tenant_id', $tenantId)
            ->where('type', 'payout')
            ->whereIn('status', $estados);

        // Bloquea las filas de retiros de esta tienda mientras dure la transaccion. Sin
        // esto, dos peticiones simultaneas leen el mismo saldo, las dos pasan la
        // comprobacion y las dos se crean — el hallazgo C3 con otro nombre.
        if ($lock) {
            $consulta->lockForUpdate();
        }

        return (float) $consulta->sum('net_amount');
    }
}
