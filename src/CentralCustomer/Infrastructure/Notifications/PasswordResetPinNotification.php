<?php

declare(strict_types=1);

namespace Src\CentralCustomer\Infrastructure\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * El PIN para recuperar la contraseña de un comprador.
 *
 * ## Arregla un callejón sin salida
 *
 * `SendCentralCustomerPasswordResetPinUseCase` generaba el PIN, lo guardaba con 15 minutos de
 * caducidad **y no lo enviaba a ningún sitio**. El controlador lo devuelve en la respuesta solo
 * en `local` y `testing`; fuera de ahí manda `null`. O sea: en desarrollo funcionaba y **en
 * producción nadie podría recuperar su contraseña**.
 *
 * ## Por qué no va en cola, a diferencia del resto
 *
 * `InboxNotification` sí se encola: un aviso que llega un segundo tarde no rompe nada. Éste no
 * es un aviso, es **un paso de un flujo que la persona está esperando delante de la pantalla**.
 * Con la cola caída, encolarlo dejaría a alguien mirando «revisa tu correo» para siempre, y sin
 * error en ninguna parte.
 *
 * ## Por qué no entra en el buzón
 *
 * Quien recupera su contraseña **no tiene sesión**: no hay campana donde pudiera verlo. Va por
 * `Notification::route('mail', …)`, a un correo suelto, sin destinatario con cuenta.
 */
final class PasswordResetPinNotification extends Notification
{
    public function __construct(
        private readonly string $pin,
        private readonly int $minutosDeValidez
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Tu código para recuperar la contraseña - OwOMarket')
            ->greeting('Recuperar tu contraseña')
            ->line('Escribe este código en la pantalla de recuperación:')
            ->line('**'.$this->pin.'**')
            ->line(sprintf('Caduca en %d minutos.', $this->minutosDeValidez))
            /*
             * El aviso de «si no fuiste tú» no es relleno: este correo se puede provocar con solo
             * conocer una dirección, así que a veces llega a quien no lo pidió. Decirle que puede
             * ignorarlo evita que cambie la contraseña por susto.
             */
            ->line('Si no pediste recuperar tu contraseña, ignora este correo: tu cuenta sigue intacta.')
            ->salutation('OwOMarket');
    }
}
