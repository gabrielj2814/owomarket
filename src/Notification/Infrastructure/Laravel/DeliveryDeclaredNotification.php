<?php

declare(strict_types=1);

namespace Src\Notification\Infrastructure\Laravel;

use Illuminate\Notifications\Notification;
use Src\CentralCustomer\Application\Service\ClaimWindow;
use Src\Monetization\Infrastructure\Eloquent\Models\OrderDeliveryConfirmation;
use Src\Tenant\Infrastructure\Eloquent\Models\Tenant;

/**
 * «La tienda dice que ya te llegó. Confírmalo.»
 *
 * **Confirmar es lo que libera el dinero del comerciante**, así que este aviso es el que hace
 * que el subsistema 3 funcione de verdad: sin él, el comprador no se entera de que le toca, la
 * venta se libera por vencimiento del plazo y de paso gasta su ventana para reclamar sin saber
 * que estaba corriendo.
 */
final class DeliveryDeclaredNotification extends Notification
{
    public function __construct(
        private readonly OrderDeliveryConfirmation $expediente,
        private readonly ClaimWindow $ventana
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'delivery.declared',
            'order_id' => $this->expediente->order_id,
            'tenant_id' => $this->expediente->tenant_id,
            'title' => 'Confirma que recibiste tu pedido',
            /*
             * Se le dice para qué sirve confirmar, no solo que confirme. Un botón sin motivo se
             * ignora; saber que el dinero llega a la tienda cuando confirmas convierte el
             * trámite en algo que el comprador entiende — y de paso le explica por qué le
             * conviene decirlo si NO le llegó.
             */
            'body' => sprintf(
                'La tienda marcó tu pedido como entregado. Al confirmar, se le paga; si algo vino mal, tienes %d días para reclamarlo.',
                $this->ventana->days(),
            ),
            'url' => $this->destino(),
        ];
    }

    /**
     * Dónde se confirma, que depende de dónde se compró.
     *
     * Una compra del marketplace central se confirma en el portal; una de escaparate, en
     * `/mis-pedidos` **de esa tienda**, que vive en otro dominio. Un enlace al portal para un
     * pedido de tienda llevaría a una pantalla donde ese pedido no aparece.
     */
    private function destino(): string
    {
        if ($this->expediente->central_order_id !== null) {
            return '/account/orders';
        }

        $dominio = Tenant::with('domains')
            ->find($this->expediente->tenant_id)
            ?->domains->first()?->domain;

        // Sin dominio no hay enlace posible, y es mejor un aviso sin enlace que uno que lleva
        // a ninguna parte.
        return $dominio === null ? '' : 'http://'.$dominio.'/mis-pedidos';
    }
}
