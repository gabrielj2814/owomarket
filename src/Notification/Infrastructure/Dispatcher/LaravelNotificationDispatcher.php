<?php

declare(strict_types=1);

namespace Src\Notification\Infrastructure\Dispatcher;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification as LaravelNotification;
use Src\CentralCustomer\Application\Service\ClaimResponseWindow;
use Src\CentralCustomer\Application\Service\ClaimWindow;
use Src\CentralCustomer\Infrastructure\Eloquent\Models\CentralCustomer;
use Src\CentralCustomer\Infrastructure\Eloquent\Models\CustomerReturnRequest;
use Src\Monetization\Infrastructure\Eloquent\Models\OrderDeliveryConfirmation;
use Src\Notification\Application\Contracts\NotificationDispatcher;
use Src\Notification\Application\Service\TenantRecipients;
use Src\Notification\Infrastructure\Laravel\ClaimOpenedNotification;
use Src\Notification\Infrastructure\Laravel\DeliveryDeclaredNotification;
use Throwable;

/**
 * Manda los avisos con las notificaciones de Laravel por debajo.
 *
 * **Nada de aquí sale hacia arriba.** Cada método envuelve su trabajo en un `try` que registra y
 * sigue. No es prudencia genérica: `ResolveReturnRequestUseCase` y `DeclareOrderDeliveredUseCase`
 * corren dentro de transacciones que revierten comisiones y liberan dinero. Una excepción desde
 * aquí desharía la operación entera.
 *
 * Es la misma regla que ya lleva escrita `MailStaleRateAlerter`: *«un fallo de correo no puede
 * tumbar la sincronización»*. Aquí el precio de equivocarse es mayor.
 *
 * **Un aviso perdido se nota y se arregla. Una reclamación deshecha por un fallo de infraestructura
 * no se nota** — el comerciante sigue debiendo el dinero y nadie sabe por qué.
 */
final class LaravelNotificationDispatcher implements NotificationDispatcher
{
    public function __construct(
        private readonly TenantRecipients $destinatarios,
        private readonly ClaimResponseWindow $plazoDeRespuesta,
        private readonly ClaimWindow $ventanaDeReclamacion
    ) {}

    public function claimOpened(string $claimId): void
    {
        $this->sinPropagar('claim.opened', $claimId, function () use ($claimId) {
            $reclamacion = CustomerReturnRequest::find($claimId);

            if ($reclamacion === null) {
                return;
            }

            $duenos = $this->destinatarios->owners($reclamacion->tenant_id);

            if ($duenos->isEmpty()) {
                /*
                 * Una tienda sin dueños es un problema de datos, no de notificaciones — pero si
                 * pasa en silencio, el reloj resolverá esa reclamación por vencimiento y nadie
                 * entenderá por qué nunca la contestaron.
                 */
                Log::warning('Reclamación sin destinatarios: la tienda no tiene dueños.', [
                    'claim_id' => $claimId,
                    'tenant_id' => $reclamacion->tenant_id,
                ]);

                return;
            }

            LaravelNotification::send(
                $duenos,
                new ClaimOpenedNotification($reclamacion, $this->plazoDeRespuesta)
            );
        });
    }

    public function deliveryDeclared(string $tenantOrderId): void
    {
        $this->sinPropagar('delivery.declared', $tenantOrderId, function () use ($tenantOrderId) {
            $expediente = OrderDeliveryConfirmation::where('order_id', $tenantOrderId)->first();

            if ($expediente === null || $expediente->customer_id === null) {
                /*
                 * Sin `customer_id` no hay a quién avisar. Le pasa a las compras de invitado:
                 * `DeclareOrderDeliveredUseCase` no encuentra cuenta central que enlazar. Es el
                 * precio aceptado del camino elegido en `PLAN_PEDIDOS_ESCAPARATE.md`, y por eso
                 * no se registra como aviso: es lo esperado, no una anomalía.
                 */
                return;
            }

            $comprador = CentralCustomer::find($expediente->customer_id);

            if ($comprador === null) {
                return;
            }

            $comprador->notify(new DeliveryDeclaredNotification($expediente, $this->ventanaDeReclamacion));
        });
    }

    /**
     * El cortafuegos. Todo lo que pase dentro se registra y muere aquí.
     */
    private function sinPropagar(string $evento, string $referencia, callable $trabajo): void
    {
        try {
            $trabajo();
        } catch (Throwable $e) {
            Log::error('No se pudo enviar una notificación.', [
                'evento' => $evento,
                'referencia' => $referencia,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
