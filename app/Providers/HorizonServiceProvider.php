<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Laravel\Horizon\HorizonApplicationServiceProvider;
use Src\Shared\Domain\ValueObjects\UserType;

class HorizonServiceProvider extends HorizonApplicationServiceProvider
{
    /**
     * Quién puede ver el panel de Horizon fuera de `local` (hallazgo N40).
     *
     * El panel enseña la carga útil de cada job: identificadores de pedidos, correos de
     * clientes y el detalle de los fallos. Deja además reintentar y borrar trabajos. O sea
     * que no es solo telemetría, es una consola de operación.
     *
     * Se ata al mismo rol que el resto del backoffice central en vez de a una lista de
     * correos —que es lo que trae Horizon por defecto— para que dar de alta un
     * superadministrador no exija acordarse de tocar este archivo.
     */
    protected function gate(): void
    {
        Gate::define('viewHorizon', function ($user = null): bool {
            return $user !== null
                && (string) ($user->type ?? '') === UserType::SUPER_ADMIN
                && ($user->is_active ?? true) !== false;
        });
    }
}
