<?php

declare(strict_types=1);

namespace Src\CentralCustomer\Application\Service;

use Illuminate\Support\Carbon;
use Src\CentralCustomer\Infrastructure\Eloquent\Models\CustomerReturnRequest;
use Src\Monetization\Infrastructure\Eloquent\Models\PlatformCommission;
use Src\Payment\Infrastructure\Eloquent\Models\CentralSetting;
use Throwable;

/**
 * Cuánto ha puesto la plataforma de su bolsillo en reclamaciones, mes a mes.
 *
 * Es el techo mensual de alarma de la decisión de garantías. `platform_covered_amount` se
 * escribía en cada reclamación desde la fase B y **nadie lo sumaba nunca**: el mes se podía
 * descontrolar entero sin que hubiera dónde verlo.
 *
 * ## Es una alarma, no un muro
 *
 * Superar el techo **no corta ningún pago**: dispara una revisión. Si fuera un muro, los
 * compradores del día 21 al 30 descubrirían que la garantía prometida no aplica por un motivo
 * que no tiene nada que ver con su compra —indefendible en atención al cliente, impublicable
 * en unos términos—. El riesgo lo acota el tope por reclamación; esto está para obligar a
 * mirar la causa antes de que el mes se vaya de las manos.
 *
 * ## Por qué no hay comando, ni tabla, ni aviso guardado
 *
 * La tentación es un comando diario que compruebe el umbral y deje un aviso. No hace falta:
 * **esto se deriva de datos que ya no se mueven**, así que un mes que se pasó sigue viéndose
 * meses después aunque nadie mirara ese día. Guardar el aviso añadiría un programador de
 * tareas, una tabla y un estado que puede desincronizarse, para contar lo mismo peor.
 *
 * ## Por qué en dólares, y convertido fila a fila
 *
 * `platform_covered_amount` está en **bolívares, a la tasa congelada de cada venta**. Eso rompe
 * un techo de dos maneras:
 *
 * 1. Un umbral en bolívares **se desactiva solo**. El número no se mueve, pero lo que significa
 *    sí: llega el mes en que salta por operar con normalidad, y una alarma que salta siempre
 *    deja de mirarse.
 * 2. Sumar bolívares de tasas distintas da un total que no corresponde a ningún dinero real, y
 *    **se queda corto siempre** —las ventas viejas tienen tasa más baja—. Un termómetro
 *    sesgado, y sesgado hacia no avisar.
 *
 * La tasa de cada venta ya está en `platform_commissions.exchange_rate`, así que cada fila se
 * convierte con la suya.
 */
final class MonthlyCoverageSpend
{
    /**
     * Dos mil dólares al mes.
     *
     * Es un punto de partida para poder mirar algo, no un número medido: hasta que haya meses
     * reales no se puede saber cuál es el correcto. Se ajusta desde Reglas de garantía.
     */
    public const TECHO_POR_DEFECTO = 2000.0;

    /** Cuántos meses se enseñan. Medio año da contexto sin convertir la pantalla en un informe. */
    private const MESES = 6;

    /** El techo en dólares. Cero es legítimo: significa «avísame de cualquier cobertura». */
    public function threshold(): float
    {
        try {
            $valor = CentralSetting::query()
                ->where('group', 'payment')
                ->where('key', 'central_claim_monthly_alarm_usd')
                ->value('value');
        } catch (Throwable) {
            return self::TECHO_POR_DEFECTO;
        }

        return $valor === null || trim((string) $valor) === ''
            ? self::TECHO_POR_DEFECTO
            : max(0.0, (float) $valor);
    }

    /**
     * Los últimos meses, del más reciente al más antiguo.
     *
     * @return array<int, array{month: string, label: string, spent_usd: float, claims: int, over: bool}>
     */
    public function lastMonths(): array
    {
        $techo = $this->threshold();
        $desde = now()->startOfMonth()->subMonths(self::MESES - 1);

        /*
         * Se agrupa en PHP y no con una función de fecha de SQL a propósito: `DATE_FORMAT` de
         * MySQL y `strftime` de SQLite no son la misma función, y los tests corren en SQLite
         * mientras producción es MySQL. Es exactamente la grieta anotada en
         * `planes/anotaciones/ENTORNO_DE_TESTS.md`, y aquí no compensa abrirla: son las
         * reclamaciones con cobertura de medio año, no un informe de millones de filas.
         */
        $reclamaciones = CustomerReturnRequest::query()
            ->whereNotNull('resolved_at')
            ->where('resolved_at', '>=', $desde)
            ->where('platform_covered_amount', '>', 0)
            ->get(['tenant_order_id', 'platform_covered_amount', 'resolved_at']);

        $tasas = $this->tasasDe($reclamaciones->pluck('tenant_order_id')->filter()->unique()->all());

        $porMes = [];
        foreach ($reclamaciones as $r) {
            $tasa = $tasas[$r->tenant_order_id] ?? 0.0;

            /*
             * Sin tasa no se puede expresar en dólares, y **no se cuenta como cero**: cero
             * diría «este mes no se gastó», que es justo lo contrario de «no sabemos cuánto».
             * Pasa solo en pedidos sin comisión registrada --sembrados a mano--, y está
             * anotado como riesgo en `index.md`.
             */
            if ($tasa <= 0.0) {
                continue;
            }

            $clave = $r->resolved_at->format('Y-m');
            $porMes[$clave] ??= ['spent_usd' => 0.0, 'claims' => 0];
            $porMes[$clave]['spent_usd'] += (float) $r->platform_covered_amount / $tasa;
            $porMes[$clave]['claims']++;
        }

        $meses = [];
        for ($i = 0; $i < self::MESES; $i++) {
            $mes = now()->startOfMonth()->subMonths($i);
            $clave = $mes->format('Y-m');
            $gastado = round($porMes[$clave]['spent_usd'] ?? 0.0, 2);

            $meses[] = [
                'month' => $clave,
                'label' => $this->etiqueta($mes),
                'spent_usd' => $gastado,
                'claims' => $porMes[$clave]['claims'] ?? 0,
                // Un techo en cero se supera con cualquier gasto, no con ninguno.
                'over' => $gastado > $techo,
            ];
        }

        return $meses;
    }

    /**
     * El mes en curso, para enseñarlo donde el administrador ya entra.
     *
     * @return array{month: string, spent_usd: float, threshold_usd: float, over: bool}
     */
    public function currentMonth(): array
    {
        $actual = $this->lastMonths()[0];

        return [
            'month' => $actual['month'],
            'spent_usd' => $actual['spent_usd'],
            'threshold_usd' => $this->threshold(),
            'over' => $actual['over'],
        ];
    }

    /**
     * La tasa congelada de cada venta.
     *
     * En dos consultas y no con un `join` porque una misma venta puede tener más de una fila de
     * comisión --una nota de crédito es otra fila-- y un join las multiplicaría por las
     * reclamaciones. Aquí basta con una tasa por pedido, y todas las filas de un pedido
     * comparten la suya.
     *
     * @param  array<int, string>  $tenantOrderIds
     * @return array<string, float>
     */
    private function tasasDe(array $tenantOrderIds): array
    {
        if ($tenantOrderIds === []) {
            return [];
        }

        return PlatformCommission::whereIn('order_id', $tenantOrderIds)
            ->get(['order_id', 'exchange_rate'])
            ->mapWithKeys(fn ($c) => [(string) $c->order_id => (float) $c->exchange_rate])
            ->all();
    }

    private function etiqueta(Carbon $mes): string
    {
        $nombres = [
            1 => 'enero', 2 => 'febrero', 3 => 'marzo', 4 => 'abril', 5 => 'mayo', 6 => 'junio',
            7 => 'julio', 8 => 'agosto', 9 => 'septiembre', 10 => 'octubre', 11 => 'noviembre',
            12 => 'diciembre',
        ];

        return $nombres[$mes->month].' '.$mes->year;
    }
}
