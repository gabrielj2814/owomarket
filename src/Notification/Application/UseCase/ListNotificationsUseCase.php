<?php

declare(strict_types=1);

namespace Src\Notification\Application\UseCase;

use Illuminate\Notifications\Notifiable;
use Src\Notification\Application\Service\EmailDelivery;
use Src\Notification\Infrastructure\Eloquent\Models\Notification;

/**
 * El buzón de quien pregunta.
 *
 * ## El destinatario entra por parámetro, nunca por la petición
 *
 * Quien llama ya ha resuelto la identidad con su guard. Aceptar un identificador del navegador
 * dejaría leer el buzón de otro — y en estos avisos hay números de pedido, productos comprados y
 * motivos de reclamación. Es el hallazgo A3 de este repositorio otra vez.
 */
final class ListNotificationsUseCase
{
    /** Un buzón no es un archivo histórico: se enseñan las últimas y ya. */
    private const LIMITE = 30;

    public function __construct(
        private readonly EmailDelivery $correo
    ) {}

    /**
     * @param  Notifiable  $destinatario
     * @return array{items: array<int, array<string, mixed>>, unread: int, email_enabled: bool}
     */
    public function execute(object $destinatario): array
    {
        $avisos = $destinatario->notifications()->limit(self::LIMITE)->get();

        return [
            'items' => $avisos->map(fn (Notification $n) => [
                'id' => $n->id,
                'read' => $n->read_at !== null,
                'created_at' => $n->created_at?->toIso8601String(),
                /*
                 * `data` se devuelve tal cual porque cada tipo de aviso decide qué guarda —el
                 * título, el cuerpo y el enlace salen de ahí—. Es lo que permite añadir un
                 * aviso nuevo en la fase 2 sin tocar ni este caso de uso ni la pantalla.
                 */
                ...$n->data,
            ])->all(),
            /*
             * El contador se calcula aquí y no contando los `items`: hay un límite, así que a
             * partir de 30 sin leer el número de la campana se quedaría clavado en 30 y dejaría
             * de significar nada.
             */
            'unread' => $destinatario->unreadNotifications()->count(),
            /*
             * Viaja con el buzón y no en una petición aparte porque la campana lo necesita para
             * pintar su interruptor: dos peticiones para abrir un desplegable es una de más.
             *
             * Es solo el interruptor de lo OPCIONAL. Los avisos críticos salen por correo
             * igualmente, y la campana lo dice.
             */
            'email_enabled' => $this->correo->emailEnabled($destinatario),
        ];
    }
}
