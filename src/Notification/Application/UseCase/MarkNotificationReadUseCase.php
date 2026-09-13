<?php

declare(strict_types=1);

namespace Src\Notification\Application\UseCase;

use Illuminate\Notifications\Notifiable;

/**
 * Marcar avisos como leídos.
 *
 * **Se marca a través del destinatario, no por el id del aviso a secas.** `$destinatario
 * ->notifications()->where('id', …)` es lo que hace imposible marcar como leído el aviso de
 * otro: sin ese filtro, quien conociera un id podría ir tachando buzones ajenos. No es solo
 * molestar: un aviso leído desaparece del contador, así que se le puede esconder a un
 * comerciante que tiene una reclamación con el reloj corriendo.
 */
final class MarkNotificationReadUseCase
{
    /**
     * @param  Notifiable  $destinatario
     * @return bool Si había un aviso suyo con ese id.
     */
    public function execute(object $destinatario, string $notificationId): bool
    {
        $aviso = $destinatario->notifications()->where('id', $notificationId)->first();

        if ($aviso === null) {
            return false;
        }

        $aviso->markAsRead();

        return true;
    }

    /** @param  Notifiable  $destinatario */
    public function all(object $destinatario): int
    {
        $sinLeer = $destinatario->unreadNotifications()->count();

        $destinatario->unreadNotifications->markAsRead();

        return $sinLeer;
    }
}
