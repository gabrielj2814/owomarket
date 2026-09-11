<?php

declare(strict_types=1);

namespace Src\CentralCustomer\Application\UseCases;

use Src\CentralCustomer\Infrastructure\Eloquent\Models\CustomerReturnRequest;
use Src\Payment\Infrastructure\Eloquent\Models\CentralSetting;
use Throwable;

/**
 * El reloj: una reclamacion que la tienda no responde se resuelve a favor del comprador
 * (subsistema 5, capa 1 del escalado).
 *
 * **Sin esto todo lo demas sobra.** Si el silencio no tiene consecuencia, ignorar es la
 * estrategia ganadora: el comerciante no responde, el comprador se cansa y la plataforma no
 * tiene nada que hacer. El reloj es lo que convierte «puede responder» en «tiene que
 * responder».
 *
 * Resolver por silencio pasa por el MISMO caso de uso que resolver a mano, con
 * `resolved_by = 'timeout'`. Dos caminos distintos hacia el mismo efecto acabarian
 * divergiendo, y aqui uno de ellos mueve dinero.
 */
final class AutoResolveStaleReturnsUseCase
{
    /**
     * Dias que tiene la tienda para responder antes de que se resuelva sin ella.
     *
     * Cinco: suficiente para atender un caso en dias laborables, corto para que el comprador
     * no se quede semanas esperando. Cero no es valido -- resolver en el acto no le daria a la
     * tienda ninguna oportunidad de responder, que es justo lo que el reloj quiere provocar.
     */
    private const DIAS_POR_DEFECTO = 5;

    public function __construct(
        private readonly ResolveReturnRequestUseCase $resolver
    ) {}

    /**
     * @return int Reclamaciones resueltas por silencio.
     */
    public function execute(): int
    {
        $limite = now()->subDays($this->diasDeEspera());

        $vencidas = CustomerReturnRequest::whereIn('status', CustomerReturnRequest::ABIERTAS)
            ->where('created_at', '<=', $limite)
            ->get();

        $resueltas = 0;

        foreach ($vencidas as $reclamacion) {
            try {
                $this->resolver->execute(
                    $reclamacion->id,
                    aprobada: true,
                    resolvedBy: 'timeout',
                    notas: 'Resuelta a favor del comprador: la tienda no respondió dentro del plazo.'
                );
                $resueltas++;
            } catch (Throwable) {
                // Una reclamacion que falla no puede frenar las demas: si la tienda la atendio
                // entre la consulta y el bucle, `ResolveReturnRequestUseCase` lanza 409 y aqui
                // simplemente se pasa a la siguiente.
                continue;
            }
        }

        return $resueltas;
    }

    private function diasDeEspera(): int
    {
        try {
            $valor = CentralSetting::query()
                ->where('group', 'payment')
                ->where('key', 'central_claim_response_days')
                ->value('value');
        } catch (Throwable) {
            return self::DIAS_POR_DEFECTO;
        }

        return $valor === null || trim((string) $valor) === ''
            ? self::DIAS_POR_DEFECTO
            : max(1, (int) $valor);
    }
}
