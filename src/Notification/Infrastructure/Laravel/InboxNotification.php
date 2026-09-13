<?php

declare(strict_types=1);

namespace Src\Notification\Infrastructure\Laravel;

use Illuminate\Notifications\Notification;

/**
 * Una notificación del buzón, cualquiera.
 *
 * ## Por qué una clase y no una por evento
 *
 * Empezó siendo una clase por aviso, que es el idiom de Laravel. Al llegar la fase 2 iban a ser
 * nueve, y las nueve hacían lo mismo: declarar `['database']` y devolver un array. Nueve ficheros
 * de veinte líneas cuya única diferencia es el texto no son nueve conceptos: son un array con
 * ceremonia.
 *
 * El texto vive ahora en `LaravelNotificationDispatcher`, que ya tiene inyectados los servicios
 * de los que salen los plazos. Añadir un aviso pasó de ser «un fichero nuevo» a «un método».
 *
 * **Cuando la fase 3 encienda el correo**, `toMail()` puede resolver la plantilla por el `type`
 * del payload (`emails.notifications.claim-opened`), así que esto no bloquea nada.
 *
 * ## El marcador `{recipient}`
 *
 * Algunas rutas del backoffice llevan el uuid del usuario --`own_user` lo comprueba-- así que el
 * enlace depende de a quién se le escribe. Laravel llama a `toDatabase()` una vez por
 * destinatario, y eso es lo que permite resolverlo aquí sin construir un payload por persona.
 */
final class InboxNotification extends Notification
{
    /** @param array<string, mixed> $payload */
    public function __construct(private readonly array $payload) {}

    /**
     * Solo `database` en las fases 1 y 2.
     *
     * El correo llega en la fase 3. Mientras tanto escribir una fila es instantáneo, no depende
     * de entregabilidad y no necesita worker — que en local importa, porque `QUEUE_CONNECTION`
     * está en `sync`.
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        $payload = $this->payload;

        if (isset($payload['url']) && is_string($payload['url'])) {
            $payload['url'] = str_replace('{recipient}', (string) $notifiable->getKey(), $payload['url']);
        }

        return $payload;
    }
}
