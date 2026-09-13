<?php

declare(strict_types=1);

namespace Src\Notification\Infrastructure\Laravel;

use Illuminate\Notifications\Notification;
use Src\CentralCustomer\Application\Service\ClaimResponseWindow;
use Src\CentralCustomer\Infrastructure\Eloquent\Models\CustomerReturnRequest;

/**
 * «Tienes una reclamación y un plazo para contestarla.»
 *
 * Es el aviso más urgente del sistema. Sin él, `AutoResolveStaleReturnsUseCase` resuelve a favor
 * del comprador cuando vence el plazo y el comerciante pierde la venta **sin haber sabido nunca
 * que tenía que contestar**. El subsistema 5 lleva construido desde el 11/09 esperando esto.
 */
final class ClaimOpenedNotification extends Notification
{
    public function __construct(
        private readonly CustomerReturnRequest $reclamacion,
        private readonly ClaimResponseWindow $plazo
    ) {}

    /**
     * Solo `database` en la fase 1.
     *
     * El correo llega en la fase 3, y entonces este método devolverá los dos. Mientras tanto
     * escribir una fila es instantáneo, no depende de entregabilidad y no necesita worker — que
     * en local importa, porque `QUEUE_CONNECTION` está en `sync`.
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
        /*
         * Los días salen de `ClaimResponseWindow`, que es el MISMO servicio que usa el reloj
         * para decidir cuándo resolver. Copiar el número aquí sería prometer un plazo que el
         * comando no respeta: la pantalla diría «te quedan 2 días» y esa noche la reclamación se
         * resolvería sola. Una cuenta atrás en la que no se puede confiar es peor que ninguna.
         */
        $diasRestantes = $this->plazo->daysLeftFrom($this->reclamacion->created_at);

        return [
            'type' => 'claim.opened',
            'claim_id' => $this->reclamacion->id,
            'tenant_id' => $this->reclamacion->tenant_id,
            'order_number' => $this->reclamacion->order_number,
            'product_name' => $this->reclamacion->product_name,
            'reason' => $this->reclamacion->reason,
            'days_left' => $diasRestantes,
            'title' => 'Tienes una reclamación sin responder',
            'body' => sprintf(
                '%s reclamó «%s» del pedido %s. Te quedan %d %s para responder; si no, se resuelve a su favor.',
                'Un comprador',
                $this->reclamacion->product_name,
                $this->reclamacion->order_number,
                $diasRestantes,
                $diasRestantes === 1 ? 'día' : 'días',
            ),
            /*
             * La ruta lleva el uuid del usuario (`own_user` lo comprueba), así que el enlace
             * solo se puede construir sabiendo a quién se le está escribiendo. Por eso sale del
             * destinatario y no de una constante.
             */
            'url' => '/tenant/owner/backoffice/'.$notifiable->id.'/returns',
        ];
    }
}
