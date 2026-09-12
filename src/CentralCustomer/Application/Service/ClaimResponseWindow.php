<?php

declare(strict_types=1);

namespace Src\CentralCustomer\Application\Service;

use DateTimeInterface;
use Illuminate\Support\Carbon;
use Src\Payment\Infrastructure\Eloquent\Models\CentralSetting;
use Throwable;

/**
 * El plazo que tiene la tienda para responder una reclamación (subsistema 5, capa 1).
 *
 * ## Por qué esto es un servicio y no un número repetido
 *
 * El reloj vive en `AutoResolveStaleReturnsUseCase`, que resuelve a favor del comprador lo que
 * la tienda no contesta. La pantalla del comerciante necesita el MISMO plazo para poder decir
 * «te quedan 2 días» — y ése es exactamente el tipo de dato que acaba existiendo dos veces con
 * dos valores distintos.
 *
 * Si divergen, el daño no es cosmético: la pantalla dice que quedan días, el comando resuelve
 * esa noche, y el comerciante pierde la venta después de que le dijéramos que tenía tiempo.
 * Una cuenta atrás en la que no se puede confiar es peor que no tener ninguna.
 *
 * El plazo es configurable (`central_claim_response_days`) y se lee en cada consulta, sin
 * caché: cambiarlo tiene que notarse en la pantalla y en el comando a la vez.
 */
final class ClaimResponseWindow
{
    /**
     * Cinco días: suficiente para atender un caso en días laborables, corto para que el
     * comprador no espere semanas. Cero no es válido — resolver en el acto no le daría a la
     * tienda ninguna oportunidad de responder, que es justo lo que el reloj quiere provocar.
     */
    private const DIAS_POR_DEFECTO = 5;

    public function days(): int
    {
        try {
            $valor = CentralSetting::query()
                ->where('group', 'payment')
                ->where('key', 'central_claim_response_days')
                ->value('value');
        } catch (Throwable) {
            // Sin poder leer el ajuste se usa el valor por defecto: dejar el plazo en cero
            // resolvería en masa a favor del comprador por un fallo de infraestructura.
            return self::DIAS_POR_DEFECTO;
        }

        return $valor === null || trim((string) $valor) === ''
            ? self::DIAS_POR_DEFECTO
            : max(1, (int) $valor);
    }

    /**
     * Cuándo se resolverá sola una reclamación abierta desde `$creadaEn`.
     */
    public function deadlineFor(DateTimeInterface $creadaEn): Carbon
    {
        return Carbon::instance(Carbon::parse($creadaEn))->addDays($this->days());
    }

    /**
     * Días que le quedan a la tienda para responder. Nunca negativo: una reclamación cuyo
     * plazo ya venció tiene cero días, no «menos tres», que en una pantalla no significa nada.
     *
     * Se redondea hacia arriba a propósito: quedan «2 días» hasta el momento en que el plazo
     * cae por debajo de uno. Quedarse corto aquí sería prometer menos tiempo del que hay, y el
     * error en esa dirección le cuesta una venta al comerciante.
     */
    public function daysLeftFrom(DateTimeInterface $creadaEn): int
    {
        $restantes = now()->diffInDays($this->deadlineFor($creadaEn), absolute: false);

        return max(0, (int) ceil($restantes));
    }
}
