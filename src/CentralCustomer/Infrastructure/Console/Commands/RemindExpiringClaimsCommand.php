<?php

declare(strict_types=1);

namespace Src\CentralCustomer\Infrastructure\Console\Commands;

use Illuminate\Console\Command;
use Src\CentralCustomer\Application\UseCases\RemindExpiringClaimsUseCase;

/**
 * El segundo aviso de una reclamacion, un dia antes de que venza.
 *
 * **Corre ANTES que `returns:auto-resolve`, y el orden no es casual.** Si corriera despues,
 * recordaria reclamaciones que el reloj acaba de resolver esa misma madrugada: un aviso para
 * actuar sobre algo que ya no se puede tocar.
 */
final class RemindExpiringClaimsCommand extends Command
{
    protected $signature = 'returns:remind-expiring';

    protected $description = 'Recuerda a la tienda las reclamaciones que vencen en un día';

    public function handle(RemindExpiringClaimsUseCase $useCase): int
    {
        $avisadas = $useCase->execute();

        // El numero cuenta las reclamaciones que TOCABA avisar, no los correos enviados: el
        // freno puede haber callado alguna que ya se aviso ayer, y eso es lo correcto.
        $this->info($avisadas === 0
            ? 'Ninguna reclamación está a punto de vencer.'
            : "Se avisó de {$avisadas} reclamación(es) a punto de vencer.");

        return self::SUCCESS;
    }
}
