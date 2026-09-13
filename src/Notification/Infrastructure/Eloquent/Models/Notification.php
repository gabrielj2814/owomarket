<?php

declare(strict_types=1);

namespace Src\Notification\Infrastructure\Eloquent\Models;

use Illuminate\Notifications\DatabaseNotification;

/**
 * Una notificación en el buzón.
 *
 * Es la de Laravel con una sola diferencia: **vive en la base central**, como las tres
 * audiencias que la reciben. La de Laravel usa la conexión por defecto, y en un proyecto
 * multi-inquilino eso significa «la que esté inicializada en este momento» — o sea, los avisos
 * de un comprador acabarían escritos en la base de la tienda que estuviera navegando.
 *
 * No daría ningún error: el aviso se guardaría y el buzón, que consulta la central, saldría
 * vacío.
 */
class Notification extends DatabaseNotification
{
    public function getConnectionName()
    {
        if (app()->runningUnitTests() || app()->environment('testing')) {
            return config('database.default');
        }

        return config('tenancy.database.central_connection') ?: 'central';
    }
}
