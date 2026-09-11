<?php

declare(strict_types=1);

namespace Src\CentralCustomer\Application\UseCases;

use Exception;
use Illuminate\Support\Facades\DB;
use Src\CentralCustomer\Infrastructure\Eloquent\Models\CustomerReturnRequest;
use Src\Monetization\Application\Service\TenantAvailableBalance;
use Src\Monetization\Application\UseCases\ReverseOrderCommissionUseCase;
use Src\Payment\Infrastructure\Eloquent\Models\CentralSetting;
use Throwable;

/**
 * Resolver una reclamación (subsistema 5, fases A y B).
 *
 * Es lo que le faltaba a `customer_return_requests` para dejar de ser un buzón: la tabla
 * existía desde agosto y **el cliente ya creaba solicitudes que nadie resolvía**.
 *
 * ## El dinero no necesitaba maquinaria nueva
 *
 * Aprobar una reclamación es **revertir la comisión**, y eso ya existe y está verificado desde
 * `CreditNoteBalanceTest`: revertir saca la venta de `netEarnings` y deja el retiro ya pagado
 * restándose. Esa resta huérfana **es la deuda del comerciante**, y emerge de la aritmética sin
 * que haya que registrarla en ningún sitio.
 *
 * El fondo de garantía tampoco se «cobra» aparte: al retener un porcentaje de cada venta,
 * reduce cuánto pudo retirar el comerciante y por tanto reduce el agujero. Por construcción.
 *
 * Lo único que sí hay que calcular aquí es **cuánto puso la plataforma de su bolsillo**: la
 * parte que el saldo del comerciante no alcanzaba a cubrir, acotada por el tope por pedido.
 * Es el número que vigila el techo mensual de alarma.
 */
final class ResolveReturnRequestUseCase
{
    /**
     * Tope de cobertura por reclamación, **en dólares**.
     *
     * Es un muro de verdad, a diferencia del techo mensual: limita la exposición y es una regla
     * publicable — el comprador sabe de antemano hasta dónde llega la cobertura. En dólares
     * porque es la unidad en la que se ponen los precios y no envejece con la tasa; se convierte
     * a bolívares con la tasa congelada de cada venta al aplicarlo.
     */
    private const TOPE_POR_DEFECTO = 200.0;

    public function __construct(
        private readonly ReverseOrderCommissionUseCase $reverse,
        private readonly TenantAvailableBalance $balance
    ) {}

    /**
     * @param  string  $resolvedBy  'merchant' | 'timeout' | 'admin'
     *
     * @throws Exception 404 si no existe, 409 si ya está resuelta, 422 si se rechaza sin motivo.
     */
    public function execute(
        string $requestId,
        bool $aprobada,
        string $resolvedBy = 'merchant',
        ?string $notas = null
    ): CustomerReturnRequest {
        return DB::transaction(function () use ($requestId, $aprobada, $resolvedBy, $notas) {
            $reclamacion = CustomerReturnRequest::where('id', $requestId)->lockForUpdate()->first();

            if ($reclamacion === null) {
                throw new Exception('Reclamación no encontrada.', 404);
            }

            if (! $reclamacion->isOpen()) {
                // Sin esto, el reloj podría resolver por silencio una reclamación que el
                // comerciante acababa de atender, y el comprador cobraría dos veces.
                throw new Exception('Esta reclamación ya está resuelta.', 409);
            }

            if (! $aprobada && ($notas === null || trim($notas) === '')) {
                throw new Exception(
                    'Indica el motivo del rechazo: el comprador necesita saber por qué se rechaza su reclamación.',
                    422
                );
            }

            $reclamacion->status = $aprobada ? 'approved' : 'rejected';
            $reclamacion->resolved_at = now();
            $reclamacion->resolved_by = $resolvedBy;
            $reclamacion->resolution_notes = $notas !== null ? trim($notas) : null;

            if ($aprobada) {
                $reclamacion->platform_covered_amount = $this->revertirYCalcularCobertura($reclamacion);
            }

            $reclamacion->save();

            return $reclamacion;
        });
    }

    /**
     * Revierte la venta y devuelve cuánto tuvo que poner la plataforma.
     *
     * El orden importa: se mira el saldo **antes** de revertir. Después, la venta ya no está en
     * `netEarnings` y el hueco que deja es justamente lo que hay que medir.
     */
    private function revertirYCalcularCobertura(CustomerReturnRequest $reclamacion): float
    {
        if ($reclamacion->tenant_order_id === null) {
            // Una reclamación sin pedido de tienda no puede revertir nada: son las anteriores
            // a esta columna. Se resuelve igual --el comprador no tiene la culpa-- pero queda
            // para ajuste manual en vez de mover dinero a ciegas.
            return 0.0;
        }

        /*
         * Se mide con `debt()` y no con `settleable()`.
         *
         * `settleable()` recorta en cero, así que pierde exactamente lo que hay que medir: si
         * la tienda ya estaba a cero, la caída visible es cero aunque la reversión la deje
         * debiendo miles. Y además se come la reserva y la comisión, que son dinero que la
         * plataforma **sí** conserva y que por tanto no tiene que poner.
         *
         * La deuda que la reversión CREA es la medida honesta de lo que la plataforma pone de
         * su bolsillo: lo que le pagó al comerciante y resulta que no le debía.
         */
        $deudaAntes = $this->balance->debt($reclamacion->tenant_id);

        $this->reverse->execute(
            $reclamacion->tenant_order_id,
            ReverseOrderCommissionUseCase::REASON_REFUNDED,
            'Reclamación '.$reclamacion->id
        );

        $deudaDespues = $this->balance->debt($reclamacion->tenant_id);

        /*
         * Lo que el saldo del comerciante absorbió, frente a lo que había que devolver.
         *
         * `settleable()` recorta en cero, así que si la tienda ya se había llevado el dinero la
         * caída visible es menor que el importe real: esa diferencia es exactamente lo que la
         * plataforma pone de su bolsillo.
         */
        $puesto = max(0.0, $deudaDespues - $deudaAntes);

        // El tope se configura en DOLARES --«cubrimos hasta $200 por pedido» es una regla
        // publicable que no envejece con la tasa-- y se convierte con la tasa congelada de
        // ESTA venta, no con la de hoy: usar dos tasas en la misma comparacion es como se
        // cuelan diferencias que nadie sabe explicar despues.
        $topeEnBolivares = $this->tope() * $this->tasaDe($reclamacion);

        return round(min($puesto, $topeEnBolivares), 2);
    }

    /**
     * La tasa congelada de la venta que se reclama.
     *
     * No la de hoy: la plataforma devuelve los bolívares que recibió, que es el mismo
     * principio que rige el saldo entero y el fondo de garantía.
     */
    private function tasaDe(CustomerReturnRequest $reclamacion): float
    {
        $tasa = DB::table('platform_commissions')
            ->where('order_id', $reclamacion->tenant_order_id)
            ->value('exchange_rate');

        return $tasa === null ? 0.0 : (float) $tasa;
    }

    private function tope(): float
    {
        try {
            $valor = CentralSetting::query()
                ->where('group', 'payment')
                ->where('key', 'central_claim_coverage_cap')
                ->value('value');
        } catch (Throwable) {
            return self::TOPE_POR_DEFECTO;
        }

        return $valor === null || trim((string) $valor) === ''
            ? self::TOPE_POR_DEFECTO
            : max(0.0, (float) $valor);
    }
}
