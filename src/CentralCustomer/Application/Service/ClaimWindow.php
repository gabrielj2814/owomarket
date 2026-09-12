<?php

declare(strict_types=1);

namespace Src\CentralCustomer\Application\Service;

use DateTimeInterface;
use Illuminate\Support\Carbon;
use Src\Payment\Infrastructure\Eloquent\Models\CentralSetting;
use Throwable;

/**
 * Cuanto tiempo tiene el comprador para reclamar, contado desde la entrega.
 *
 * ## Por que no se lee de la reserva de garantia
 *
 * El colchon que paga una reclamacion es la reserva del subsistema 4
 * (`central_guarantee_reserve_days`, 60 dias), y la tentacion evidente es leer ese ajuste
 * directamente para que los dos numeros no puedan divergir.
 *
 * No se hace, y es deliberado: son **dos preguntas de negocio distintas**. Una es cuanto
 * tiempo tiene el comprador; la otra, cuanto dinero se aparta y por cuanto. Bajar la reserva a
 * 30 dias para mejorar el flujo de caja de los comerciantes le recortaria al comprador la
 * mitad de su plazo sin que nadie lo hubiera decidido.
 *
 * Empiezan valiendo lo mismo a proposito. Si un dia divergen sera porque alguien lo quiso.
 *
 * ## Por que no se lee de `central_payout_hold_days`
 *
 * Ese ajuste vale **1 dia por defecto y cero es legitimo** --significa «lo entregado se puede
 * retirar en el acto»--. Atar la ventana de reclamacion ahi dejaria al comprador un dia para
 * reclamar, o ninguno. Mide cuando cobra el comerciante, no cuanto responde la plataforma.
 */
final class ClaimWindow
{
    /**
     * Catorce dias.
     *
     * Empezo en 60 --el mismo numero que retenia la reserva-- y bajo a dos semanas por una
     * razon de exposicion, no de comodidad: **el reembolso al comprador se paga en bolivares
     * nominales**, los que entraron. Cuanto mas tiempo pasa entre la compra y la devolucion,
     * menos vale lo que se devuelve, y esa perdida se la come el comprador. Acortar la ventana
     * es lo que hace que la promesa siga valiendo lo que decia.
     *
     * De rebote deja retirar antes: no tiene sentido retener el fondo de garantia meses
     * despues de que reclamar sea imposible. Ver `ReleaseOrderCommissionUseCase`, que baja a
     * 30 dias por esto mismo.
     *
     * Que dos semanas sea legal en Venezuela es pregunta de abogado, y esta anotada como tal.
     * Si resulta que hace falta mas, se sube desde la pantalla de Reglas de garantia sin tocar
     * codigo: para eso es un ajuste.
     */
    public const DIAS_POR_DEFECTO = 14;

    public function days(): int
    {
        try {
            $valor = CentralSetting::query()
                ->where('group', 'payment')
                ->where('key', 'central_claim_window_days')
                ->value('value');
        } catch (Throwable) {
            // Un fallo al leer el ajuste no puede cerrar las reclamaciones: se cae al valor
            // por defecto, que es el permisivo.
            return self::DIAS_POR_DEFECTO;
        }

        return $valor === null || trim((string) $valor) === ''
            ? self::DIAS_POR_DEFECTO
            : max(1, (int) $valor);
    }

    /**
     * Hasta cuando se puede reclamar un pedido entregado en `$entregadoEl`.
     *
     * Minimo un dia (lo garantiza `days()`): un ajuste a cero cerraria de golpe las
     * reclamaciones de todos los pedidos entregados, que no es una decision que deba poder
     * tomarse por accidente al escribir en una caja de texto.
     */
    public function deadlineFor(DateTimeInterface $entregadoEl): Carbon
    {
        return Carbon::instance(Carbon::parse($entregadoEl))->addDays($this->days());
    }

    public function isOpenFor(DateTimeInterface $entregadoEl): bool
    {
        return now()->lessThanOrEqualTo($this->deadlineFor($entregadoEl));
    }
}
