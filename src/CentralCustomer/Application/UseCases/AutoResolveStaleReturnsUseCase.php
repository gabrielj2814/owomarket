<?php

declare(strict_types=1);

namespace Src\CentralCustomer\Application\UseCases;

use Src\CentralCustomer\Application\Service\ClaimResponseWindow;
use Src\CentralCustomer\Infrastructure\Eloquent\Models\CustomerReturnRequest;
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
     * El plazo NO se calcula aqui: lo dice `ClaimResponseWindow`, el mismo servicio que lee la
     * pantalla del comerciante para mostrar la cuenta atras. Si cada uno tuviera su copia,
     * podrian decir cosas distintas -- y entonces la pantalla prometeria dias que este comando
     * no respeta, y el comerciante perderia la venta despues de que le dijeramos que tenia
     * tiempo.
     */
    public function __construct(
        private readonly ResolveReturnRequestUseCase $resolver,
        private readonly ClaimResponseWindow $plazo
    ) {}

    /**
     * @return int Reclamaciones resueltas por silencio.
     */
    public function execute(): int
    {
        $limite = now()->subDays($this->plazo->days());

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
}
