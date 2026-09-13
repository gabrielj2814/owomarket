<?php

declare(strict_types=1);

namespace Src\Admin\Infrastructure\Console\Commands;

use Illuminate\Console\Command;
use Src\Notification\Application\Contracts\NotificationDispatcher;

/**
 * El empujon que le faltaba al techo mensual de alarma.
 *
 * `MonthlyCoverageSpend` calcula el gasto desde el 12/09/2026 y lo pinta donde el administrador
 * entra, pero **nada le obligaba a mirarlo**. La decision de garantias pide una «revision
 * obligatoria» al superar el techo, y obligar era justo lo que faltaba.
 *
 * ## Diario, no mensual
 *
 * El plan decia «mensual». Se hace diario a proposito: **una comprobacion el dia 1 avisa de un
 * mes que ya termino**, y el techo existe para poder intervenir antes de que el mes se
 * descontrole. Corriendo cada dia, el aviso llega el dia en que se cruza.
 *
 * Lo que evita el correo diario no es la frecuencia del comando, es el freno: una vez pasado el
 * techo, el mes sigue pasado manana, asi que `NotificationThrottle` deja salir **un aviso por
 * mes**.
 */
final class CheckCoverageCeilingCommand extends Command
{
    protected $signature = 'coverage:check-ceiling';

    protected $description = 'Avisa a la plataforma si el gasto mensual en coberturas pasó del techo';

    public function handle(NotificationDispatcher $avisos): int
    {
        // El propio despachador decide si hay algo que decir: comprueba el techo y el freno.
        // Duplicar esa comprobacion aqui daria dos sitios que pueden divergir.
        $avisos->coverageCeilingExceeded();

        $this->info('Comprobación del techo mensual completada.');

        return self::SUCCESS;
    }
}
