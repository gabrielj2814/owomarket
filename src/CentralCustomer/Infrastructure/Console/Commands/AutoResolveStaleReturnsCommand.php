<?php

declare(strict_types=1);

namespace Src\CentralCustomer\Infrastructure\Console\Commands;

use Illuminate\Console\Command;
use Src\CentralCustomer\Application\UseCases\AutoResolveStaleReturnsUseCase;

/**
 * El reloj de las reclamaciones (subsistema 5).
 *
 * Sin este comando, el silencio de la tienda no tiene consecuencia y todo el escalado sobra:
 * ignorar vuelve a ser la estrategia ganadora.
 */
final class AutoResolveStaleReturnsCommand extends Command
{
    protected $signature = 'returns:auto-resolve';

    protected $description = 'Resuelve a favor del comprador las reclamaciones que la tienda no respondió dentro del plazo';

    public function handle(AutoResolveStaleReturnsUseCase $useCase): int
    {
        $resueltas = $useCase->execute();

        $this->info($resueltas === 0
            ? 'No había reclamaciones vencidas.'
            : "Se resolvieron {$resueltas} reclamación(es) por falta de respuesta de la tienda.");

        return self::SUCCESS;
    }
}
