<?php

declare(strict_types=1);

namespace Src\Notification\Application\Service;

use Illuminate\Database\Eloquent\Relations\Relation;
use Src\Notification\Infrastructure\Eloquent\Models\NotificationPreference;
use Throwable;

/**
 * Decide si un aviso concreto sale además por correo.
 *
 * ## Dos reglas, y la segunda existe porque la primera sola no basta
 *
 * **Lo crítico se manda siempre.** Son los avisos con un reloj o dinero detrás: si el
 * comerciante no se entera de una reclamación, la pierde a los cinco días; si el comprador no se
 * entera de una entrega, gasta su ventana para reclamar sin saberlo; si no se entera de que su
 * KYC se resolvió, no sabe por qué no puede cobrar.
 *
 * Que se pueda apagar sería como dejar apagar la alarma de incendios. Y no es paternalismo: el
 * daño de perderse uno de estos lo paga quien lo apagó, pero la plataforma es quien puso el
 * reloj.
 *
 * **El resto es opcional, y llega apagado.** El correo es intrusivo y cuesta entregabilidad: un
 * administrador que reciba correo de cada KYC enviado deja de leerlos, y entonces tampoco lee
 * los que importan.
 */
final class EmailDelivery
{
    /**
     * Los avisos que se mandan por correo aunque nadie lo pida.
     *
     * La regla para entrar aquí es una: **que perdérselo cueste dinero o deje correr un plazo**.
     * Añadir uno es decidir que le puede llegar correo a alguien que no lo pidió, así que no se
     * hace sin una razón de ese tamaño.
     *
     * Los tres primeros son los que la decisión de garantías deja sin poder apagar. Los dos de
     * la fase 4 entraron después, y por la misma regla:
     *
     * - `claim.expiring` es el **último** aviso antes de que el reloj resuelva en contra. Si el
     *   primero es crítico, el último lo es más: es la diferencia entre perder una venta y
     *   llegar a tiempo.
     * - `coverage.ceiling` es la «revisión obligatoria» que pide la decisión de garantías.
     *   Dejarla opcional sería volver al problema que vino a resolver: el número estaba a la
     *   vista y nadie miraba. Sale una vez al mes como mucho, así que no hay riesgo de ruido.
     */
    public const CRITICOS = [
        'claim.opened',
        'claim.expiring',
        'delivery.declared',
        'kyc.reviewed',
        'coverage.ceiling',
    ];

    public function shouldEmail(object $destinatario, string $tipo): bool
    {
        if (in_array($tipo, self::CRITICOS, true)) {
            return true;
        }

        return $this->emailEnabled($destinatario);
    }

    public function emailEnabled(object $destinatario): bool
    {
        try {
            return (bool) NotificationPreference::query()
                ->where('notifiable_type', $this->alias($destinatario))
                ->where('notifiable_id', (string) $destinatario->getKey())
                ->value('email_enabled');
        } catch (Throwable) {
            /*
             * Sin poder leer la preferencia se manda **solo lo crítico**, que es lo que ya
             * decidió el método de arriba. Caer del lado de no mandar es lo correcto aquí: el
             * error opuesto es llenar de correo a quien no lo pidió, y eso no se deshace.
             */
            return false;
        }
    }

    public function setEmailEnabled(object $destinatario, bool $activado): void
    {
        NotificationPreference::updateOrCreate(
            [
                'notifiable_type' => $this->alias($destinatario),
                'notifiable_id' => (string) $destinatario->getKey(),
            ],
            ['email_enabled' => $activado],
        );
    }

    /**
     * El alias corto del mapa de morfismos ('staff', 'customer'), el mismo que guarda
     * `notifications`.
     *
     * Se resuelve por el mapa y no con `get_class()` porque hay cuatro clases `User` sobre la
     * tabla `users`: guardar el nombre de la clase haría que la preferencia de una persona no se
     * encontrara al cargarla con otra.
     */
    private function alias(object $destinatario): string
    {
        $alias = array_search($destinatario::class, Relation::morphMap(), strict: true);

        return $alias === false ? $destinatario::class : (string) $alias;
    }
}
