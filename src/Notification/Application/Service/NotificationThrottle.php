<?php

declare(strict_types=1);

namespace Src\Notification\Application\Service;

use DateTimeInterface;
use Illuminate\Support\Facades\Cache;
use Throwable;

/**
 * Un aviso, una vez.
 *
 * ## Por qué existe ahora y no antes
 *
 * Las fases 1 a 3 no lo necesitaban: una reclamación se abre una vez, una entrega se declara una
 * vez, un retiro se resuelve una vez. **Construir un freno antes de tener qué frenar es
 * inventarse un problema**, así que se dejó anotado y no se escribió.
 *
 * La fase 4 sí lo necesita, porque sus dos avisos los dispara un comando que corre **todos los
 * días** sobre un estado que no cambia: el mes sigue pasado del techo mañana, y la reclamación
 * sigue a punto de vencer hasta que alguien la conteste. Sin freno, el mismo aviso saldría cada
 * madrugada.
 *
 * ## La lección está escrita desde agosto
 *
 * `MailStaleRateAlerter` ya lleva este mismo mecanismo, y su comentario dice por qué:
 * `exchange-rate:sync-bcv` corre tres veces al día, así que un BCV caído una semana produciría 15
 * correos *«y el aviso dejaría de leerse justo cuando importa»*.
 *
 * Un aviso repetido no avisa el doble: **avisa menos**, porque enseña a ignorarlo.
 *
 * ## Si la caché falla, se avisa
 *
 * `Cache::add` es atómico y ése es el punto: dos pasadas simultáneas no pueden colarse las dos.
 * Pero si la caché no responde, la decisión es **dejar pasar el aviso**. Un aviso de más molesta;
 * uno de menos deja a un comerciante perdiendo una venta por silencio.
 */
final class NotificationThrottle
{
    /**
     * ¿Toca mandar este aviso, o ya se mandó?
     *
     * Devuelve `true` **solo la primera vez** dentro de la ventana. La marca se pone en el mismo
     * momento de responder que sí: entre preguntar y mandar no cabe una segunda pasada.
     */
    public function shouldSend(string $clave, DateTimeInterface|int $durante): bool
    {
        try {
            return Cache::add('notif-throttle:'.$clave, true, $durante);
        } catch (Throwable) {
            return true;
        }
    }
}
