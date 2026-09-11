<?php

declare(strict_types=1);

namespace Src\Monetization\Infrastructure\Console\Commands;

use Illuminate\Console\Command;
use Src\Monetization\Application\UseCases\ReleaseUnconfirmedDeliveriesUseCase;

/**
 * Libera las entregas que el comprador nunca llego a confirmar (subsistema 3).
 *
 * Este comando no es un extra del subsistema: es lo que impide que sea una trampa. Sin el,
 * un comprador que recibe su paquete y no vuelve a entrar en la plataforma deja el dinero de
 * la tienda retenido para siempre.
 */
final class ReleaseUnconfirmedDeliveriesCommand extends Command
{
    protected $signature = 'deliveries:release-unconfirmed';

    protected $description = 'Libera el dinero de las entregas declaradas que el comprador no confirmó dentro del plazo';

    public function handle(ReleaseUnconfirmedDeliveriesUseCase $useCase): int
    {
        $liberadas = $useCase->execute();

        $this->info($liberadas === 0
            ? 'No había entregas vencidas pendientes de liberar.'
            : "Se liberaron {$liberadas} entrega(s) por vencimiento del plazo de confirmación.");

        return self::SUCCESS;
    }
}
