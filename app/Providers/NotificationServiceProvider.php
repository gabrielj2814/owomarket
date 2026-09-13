<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\ServiceProvider;
use Src\CentralCustomer\Infrastructure\Eloquent\Models\CentralCustomer;
use Src\Notification\Application\Contracts\NotificationDispatcher;
use Src\Notification\Infrastructure\Dispatcher\LaravelNotificationDispatcher;
use Src\User\Infrastructure\Eloquent\Models\User;

/**
 * El módulo de notificaciones (fase 1 de `planes/futuros/PLAN_NOTIFICACIONES.md`).
 */
class NotificationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(NotificationDispatcher::class, LaravelNotificationDispatcher::class);
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            /*
            | Los dos comandos de la fase 4: lo periodico.
            |
            | Se registran aqui y no junto a los de pedidos porque son de notificaciones --lo
            | unico que hacen es decidir cuando toca avisar-- y porque este proveedor ya existe:
            | un fichero nuevo para dos lineas no se justifica.
            */
            $this->commands([
                \Src\CentralCustomer\Infrastructure\Console\Commands\RemindExpiringClaimsCommand::class,
                \Src\Admin\Infrastructure\Console\Commands\CheckCoverageCeilingCommand::class,
            ]);
        }

        /*
        |----------------------------------------------------------------------
        | Quién puede recibir un aviso, con un nombre corto y estable
        |----------------------------------------------------------------------
        |
        | La tabla `notifications` es polimórfica: guarda en `notifiable_type` a qué clase
        | pertenece el destinatario. Sin mapa, guarda el nombre completo de la clase — y en este
        | repositorio hay **CUATRO clases `User` distintas sobre la tabla `users`**:
        |
        |   Src\Admin\...\User   Src\Authentication\...\User   Src\Tenant\...\User   Src\User\...\User
        |
        | La misma persona acumularía avisos bajo cuatro tipos según qué clase la cargara, y una
        | consulta filtrada por uno se dejaría los otros tres fuera. **El fallo no da ningún
        | error: simplemente faltan avisos**, que es la peor forma de fallar para un buzón.
        |
        | Con el mapa, el destinatario se normaliza a dos clases canónicas y en la base se
        | guarda 'staff' o 'customer'. De rebote, mover o renombrar una clase deja de romper las
        | filas ya guardadas.
        */
        /*
        | `morphMap` y NO `enforceMorphMap`. El segundo obliga a que **toda** relación
        | polimórfica de la aplicación esté en el mapa y lanza para las que no: reventaría
        | `model_has_roles` de Spatie Permissions y el `addressable` de las direcciones, que no
        | tienen nada que ver con esto.
        */
        Relation::morphMap([
            'staff' => User::class,
            'customer' => CentralCustomer::class,
        ]);
    }
}
