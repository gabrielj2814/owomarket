<?php

declare(strict_types=1);

namespace Src\Notification\Infrastructure\Eloquent\Concerns;

use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Notifications\Notifiable;
use Src\Notification\Infrastructure\Eloquent\Models\Notification;

/**
 * `Notifiable` de Laravel, pero escribiendo en la base central.
 *
 * El `notifications()` original apunta a `Illuminate\Notifications\DatabaseNotification`, que
 * usa la conexión por defecto. En este proyecto eso sería la del inquilino que estuviera
 * inicializado, así que un aviso escrito mientras se navega una tienda acabaría en la base de
 * esa tienda — y el buzón, que lee la central, lo enseñaría vacío. Sin error.
 *
 * Los canales de Laravel llaman a `routeNotificationFor('database')`, que a su vez usa esta
 * relación: sustituirla aquí es lo único que hace falta para que todo el mecanismo del framework
 * escriba donde debe.
 */
trait NotifiableCentral
{
    use Notifiable;

    public function notifications(): MorphMany
    {
        return $this->morphMany(Notification::class, 'notifiable')->latest();
    }
}
