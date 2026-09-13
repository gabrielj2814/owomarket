<?php

declare(strict_types=1);

namespace Src\Notification\Infrastructure\Laravel;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Src\Notification\Application\Service\EmailDelivery;

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
 * El texto vive en `LaravelNotificationDispatcher`, que ya tiene inyectados los servicios de los
 * que salen los plazos. Añadir un aviso es un método, no un fichero.
 *
 * ## En cola desde la fase 3
 *
 * Mandar un correo dentro de la petición la alarga y la ata a que el servidor SMTP responda. En
 * local `QUEUE_CONNECTION=sync`, así que esto **sigue corriendo en línea y no hace falta ningún
 * worker para probarlo**; en producción lo recoge Horizon.
 *
 * Encolar arrastra también la escritura de la fila del buzón, y es un precio aceptado: con la
 * cola caída no habría ni correo ni buzón, pero una cola caída es un problema que Horizon
 * reporta, no uno que se descubra por un aviso perdido.
 *
 * ## El marcador `{recipient}`
 *
 * Algunas rutas del backoffice llevan el uuid del usuario --`own_user` lo comprueba-- así que el
 * enlace depende de a quién se le escribe. Laravel llama a `toDatabase()` y a `toMail()` una vez
 * por destinatario, y eso es lo que permite resolverlo aquí sin construir un payload por persona.
 */
final class InboxNotification extends Notification implements ShouldQueue
{
    /*
     * `Queueable` de `Illuminate\Bus`, no `InteractsWithQueue`. Es el que aporta `$connection`,
     * `$queue` y `$delay`, que el mecanismo de colas lee directamente: sin el, encolar lanza
     * «Undefined property: $connection» --y con el despachador capturando, el aviso se pierde
     * sin que nadie vea el error--.
     */
    use Queueable;

    /** @param array<string, mixed> $payload */
    public function __construct(private readonly array $payload) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        $canales = ['database'];

        $tipo = (string) ($this->payload['type'] ?? '');

        if (app(EmailDelivery::class)->shouldEmail($notifiable, $tipo)) {
            $canales[] = 'mail';
        }

        return $canales;
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return $this->resuelto($notifiable);
    }

    /**
     * El correo dice lo mismo que el buzón, con un botón para ir a resolverlo.
     *
     * Se usa la plantilla que Laravel ya trae en vez de escribir una propia. No es pereza: los
     * tres correos que existen en el proyecto construyen su HTML a mano, cada uno el suyo, y son
     * tres plantillas que nadie mantiene a la vez. Esta se ve bien, es responsive y respeta el
     * `MAIL_FROM_NAME`.
     *
     * **Sin enlace no hay botón.** Un correo con un botón que no lleva a ninguna parte es peor
     * que uno sin botón — y pasa de verdad: una entrega de escaparate cuya tienda no tiene
     * dominio registrado.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $datos = $this->resuelto($notifiable);

        $mensaje = (new MailMessage)
            ->subject($datos['title'] ?? 'Tienes un aviso de OwOMarket')
            ->greeting($datos['title'] ?? 'Tienes un aviso')
            ->line($datos['body'] ?? '');

        if (! empty($datos['url'])) {
            $mensaje->action('Ver en OwOMarket', url($datos['url']));
        }

        /*
         * Se dice de dónde viene el correo y cómo dejar de recibirlo. No es cortesía: un correo
         * que no explica por qué llegó acaba marcado como spam, y eso se lleva por delante los
         * avisos que sí importan.
         */
        return $mensaje->salutation('OwOMarket')
            ->line(in_array($datos['type'] ?? '', EmailDelivery::CRITICOS, true)
                ? 'Recibes este aviso porque afecta a tu dinero o a un plazo en curso.'
                : 'Recibes este correo porque lo activaste en tus notificaciones. Puedes desactivarlo desde la campana.');
    }

    /**
     * @return array<string, mixed>
     */
    private function resuelto(object $notifiable): array
    {
        $payload = $this->payload;

        if (isset($payload['url']) && is_string($payload['url'])) {
            $payload['url'] = str_replace('{recipient}', (string) $notifiable->getKey(), $payload['url']);
        }

        return $payload;
    }
}
