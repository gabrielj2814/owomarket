<?php

declare(strict_types=1);

namespace Src\Notification\Infrastructure\Dispatcher;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification as LaravelNotification;
use Src\CentralCustomer\Application\Service\ClaimResponseWindow;
use Src\CentralCustomer\Application\Service\ClaimWindow;
use Src\CentralCustomer\Application\Service\MonthlyCoverageSpend;
use Src\CentralCustomer\Infrastructure\Eloquent\Models\CentralCustomer;
use Src\CentralCustomer\Infrastructure\Eloquent\Models\CustomerReturnRequest;
use Src\Monetization\Infrastructure\Eloquent\Models\CommissionSettlement;
use Src\Monetization\Infrastructure\Eloquent\Models\OrderDeliveryConfirmation;
use Src\Monetization\Infrastructure\Eloquent\Models\TenantPlanChangeRequest;
use Src\Notification\Application\Contracts\NotificationDispatcher;
use Src\Notification\Application\Service\NotificationRecipients;
use Src\Notification\Application\Service\NotificationThrottle;
use Src\Notification\Infrastructure\Laravel\InboxNotification;
use Src\Tenant\Infrastructure\Eloquent\Models\Tenant;
use Src\Tenant\Infrastructure\Eloquent\Models\TenantKycProfile;
use Throwable;

/**
 * Manda los avisos con las notificaciones de Laravel por debajo.
 *
 * Aquí vive **lo que dice cada aviso y a quién va**. El texto está aquí y no en una clase por
 * evento porque nueve clases que solo devuelven un array no son nueve conceptos; y está aquí y
 * no en los casos de uso porque decidir destinatarios no es asunto del dominio.
 *
 * ## Nada de aquí sale hacia arriba
 *
 * Cada método envuelve su trabajo en un `try` que registra y sigue. No es prudencia genérica:
 * `ResolveReturnRequestUseCase` revierte comisiones y `ApproveCentralPayoutRequestUseCase` marca
 * un retiro como pagado, los dos dentro de transacciones. Una excepción desde aquí desharía la
 * operación entera.
 *
 * Es la misma regla que ya lleva escrita `MailStaleRateAlerter`: *«un fallo de correo no puede
 * tumbar la sincronización»*. Aquí el precio de equivocarse es mayor.
 *
 * ## Las URLs del personal llevan `{recipient}`
 *
 * Varias rutas del backoffice incluyen el uuid del usuario (`own_user` lo comprueba), así que el
 * enlace depende de a quién se le escribe. `InboxNotification` lo sustituye por destinatario.
 */
final class LaravelNotificationDispatcher implements NotificationDispatcher
{
    public function __construct(
        private readonly NotificationRecipients $destinatarios,
        private readonly ClaimResponseWindow $plazoDeRespuesta,
        private readonly ClaimWindow $ventanaDeReclamacion,
        private readonly MonthlyCoverageSpend $cobertura,
        private readonly NotificationThrottle $freno
    ) {}

    // ---------------------------------------------------------------- Garantías

    public function claimOpened(string $claimId): void
    {
        $this->sinPropagar('claim.opened', $claimId, function () use ($claimId) {
            $reclamacion = CustomerReturnRequest::find($claimId);

            if ($reclamacion === null) {
                return;
            }

            $dias = $this->plazoDeRespuesta->daysLeftFrom($reclamacion->created_at);

            $this->aLosDuenos($reclamacion->tenant_id, [
                'type' => 'claim.opened',
                'claim_id' => $reclamacion->id,
                'order_number' => $reclamacion->order_number,
                'days_left' => $dias,
                'title' => 'Tienes una reclamación sin responder',
                'body' => sprintf(
                    'Un comprador reclamó «%s» del pedido %s. Te quedan %d %s para responder; si no, se resuelve a su favor.',
                    $reclamacion->product_name,
                    $reclamacion->order_number,
                    $dias,
                    $dias === 1 ? 'día' : 'días',
                ),
                'url' => '/tenant/owner/backoffice/{recipient}/returns',
            ], contexto: 'reclamación abierta');
        });
    }

    public function claimResolved(string $claimId): void
    {
        $this->sinPropagar('claim.resolved', $claimId, function () use ($claimId) {
            $reclamacion = CustomerReturnRequest::find($claimId);

            if ($reclamacion === null) {
                return;
            }

            $porSilencio = $reclamacion->resolved_by === 'timeout';
            $aprobada = $reclamacion->status === 'approved';

            $this->alComprador($reclamacion->customer_id, [
                'type' => 'claim.resolved',
                'claim_id' => $reclamacion->id,
                'status' => $reclamacion->status,
                'title' => $aprobada ? 'Tu reclamación fue aprobada' : 'Tu reclamación fue rechazada',
                /*
                 * La misma distinción que hace la pantalla del comprador: **una aprobación por
                 * silencio no la aprobó la tienda**. Atribuirle una decisión que no tomó es
                 * mentir, y contradice la propia explicación de que venció el plazo.
                 */
                'body' => $aprobada
                    ? ($porSilencio
                        ? sprintf('La tienda no respondió dentro del plazo, así que tu reclamación de «%s» se resolvió a tu favor.', $reclamacion->product_name)
                        : sprintf('La tienda aceptó tu reclamación de «%s»: se te devuelve el importe.', $reclamacion->product_name))
                    : sprintf('La tienda rechazó tu reclamación de «%s».%s', $reclamacion->product_name, $this->motivo($reclamacion->resolution_notes)),
                'url' => '/account/returns',
            ]);

            if (! $porSilencio) {
                return;
            }

            /*
             * Y al comerciante, **solo cuando la resolvió el reloj**. Si respondió él ya sabe lo
             * que decidió; enterarse de que perdió una venta por no contestar es lo único que
             * hace que la siguiente sí se conteste.
             */
            $this->aLosDuenos($reclamacion->tenant_id, [
                'type' => 'claim.timedout',
                'claim_id' => $reclamacion->id,
                'order_number' => $reclamacion->order_number,
                'title' => 'Perdiste una reclamación por no responder',
                'body' => sprintf(
                    'Venció el plazo de la reclamación de «%s» (pedido %s) y se resolvió a favor del comprador. Cuenta como «sin responder» y afecta a tu reputación.',
                    $reclamacion->product_name,
                    $reclamacion->order_number,
                ),
                'url' => '/tenant/owner/backoffice/{recipient}/returns',
            ], contexto: 'reclamación vencida');
        });
    }

    public function deliveryDeclared(string $tenantOrderId): void
    {
        $this->sinPropagar('delivery.declared', $tenantOrderId, function () use ($tenantOrderId) {
            $expediente = OrderDeliveryConfirmation::where('order_id', $tenantOrderId)->first();

            /*
             * Sin `customer_id` no hay a quién avisar. Le pasa a las compras de invitado:
             * `DeclareOrderDeliveredUseCase` no encuentra cuenta central que enlazar. Es el
             * precio aceptado del camino elegido para los pedidos de escaparate, y por eso no
             * se registra como aviso: es lo esperado, no una anomalía.
             */
            if ($expediente === null || $expediente->customer_id === null) {
                return;
            }

            $this->alComprador($expediente->customer_id, [
                'type' => 'delivery.declared',
                'order_id' => $expediente->order_id,
                'title' => 'Confirma que recibiste tu pedido',
                /*
                 * Se le dice para qué sirve confirmar, no solo que confirme. Un botón sin motivo
                 * se ignora; saber que el dinero llega a la tienda cuando confirmas convierte el
                 * trámite en algo que se entiende — y de paso le explica por qué le conviene
                 * decirlo si NO le llegó.
                 */
                'body' => sprintf(
                    'La tienda marcó tu pedido como entregado. Al confirmar, se le paga; si algo vino mal, tienes %d días para reclamarlo.',
                    $this->ventanaDeReclamacion->days(),
                ),
                'url' => $this->dondeSeConfirma($expediente),
            ]);
        });
    }

    // ---------------------------------------------------------------- Identidad

    public function kycSubmitted(string $kycProfileId): void
    {
        $this->sinPropagar('kyc.submitted', $kycProfileId, function () use ($kycProfileId) {
            $perfil = TenantKycProfile::find($kycProfileId);

            if ($perfil === null) {
                return;
            }

            $this->aLaPlataforma([
                'type' => 'kyc.submitted',
                'kyc_id' => $perfil->id,
                'tenant_id' => $perfil->tenant_id,
                'title' => 'Una tienda envió su identidad',
                // El nombre de la tienda, no el legal: el dato de identidad no viaja a un buzón.
                'body' => sprintf('%s está esperando verificación. Sin ella no puede cobrar.', $this->nombreDeTienda($perfil->tenant_id)),
                'url' => '/admin/backoffice/{recipient}/kyc',
            ]);
        });
    }

    public function kycReviewed(string $kycProfileId): void
    {
        $this->sinPropagar('kyc.reviewed', $kycProfileId, function () use ($kycProfileId) {
            $perfil = TenantKycProfile::find($kycProfileId);

            if ($perfil === null) {
                return;
            }

            $verificado = $perfil->status === 'verified';

            $this->aLosDuenos($perfil->tenant_id, [
                'type' => 'kyc.reviewed',
                'kyc_id' => $perfil->id,
                'status' => $perfil->status,
                'title' => $verificado ? 'Tu identidad quedó verificada' : 'Tu identidad no se pudo verificar',
                'body' => $verificado
                    ? 'Ya puedes solicitar retiros de tu saldo.'
                    : 'Corrige lo indicado y vuelve a enviarla. Sin verificar no puedes cobrar.'.$this->motivo($perfil->rejection_reason),
                'url' => '/tenant/owner/backoffice/{recipient}/wallet',
            ], contexto: 'KYC revisado');
        });
    }

    // ---------------------------------------------------------------- Dinero

    public function payoutRequested(string $settlementId): void
    {
        $this->sinPropagar('payout.requested', $settlementId, function () use ($settlementId) {
            $retiro = CommissionSettlement::find($settlementId);

            if ($retiro === null) {
                return;
            }

            $this->aLaPlataforma([
                'type' => 'payout.requested',
                'settlement_id' => $retiro->id,
                'tenant_id' => $retiro->tenant_id,
                'title' => 'Hay un retiro esperando',
                'body' => sprintf(
                    '%s pidió retirar %s. Está esperando tu aprobación.',
                    $this->nombreDeTienda($retiro->tenant_id),
                    $this->importe($retiro),
                ),
                'url' => '/admin/backoffice/{recipient}/payouts',
            ]);
        });
    }

    public function payoutResolved(string $settlementId): void
    {
        $this->sinPropagar('payout.resolved', $settlementId, function () use ($settlementId) {
            $retiro = CommissionSettlement::find($settlementId);

            if ($retiro === null) {
                return;
            }

            /*
             * `settled`, no `paid`. Es el estado que pone `ApproveCentralPayoutRequestUseCase`
             * al aprobar; el rechazo deja `cancelled`. Leerlo del codigo y no suponerlo importa:
             * con el estado equivocado este aviso le diria «rechazado» a quien acaba de cobrar.
             */
            $pagado = $retiro->status === 'settled';

            $this->aLosDuenos($retiro->tenant_id, [
                'type' => 'payout.resolved',
                'settlement_id' => $retiro->id,
                'status' => $retiro->status,
                'title' => $pagado ? 'Tu retiro fue pagado' : 'Tu retiro fue rechazado',
                'body' => $pagado
                    ? sprintf('Se te transfirieron %s. La referencia está en tu billetera.', $this->importe($retiro))
                    : sprintf('El retiro de %s no se aprobó.%s', $this->importe($retiro), $this->motivo($retiro->notes)),
                'url' => '/tenant/owner/backoffice/{recipient}/wallet',
            ], contexto: 'retiro resuelto');
        });
    }

    // ---------------------------------------------------------------- Suscripción

    public function planChangeRequested(string $requestId): void
    {
        $this->sinPropagar('plan.requested', $requestId, function () use ($requestId) {
            $solicitud = TenantPlanChangeRequest::find($requestId);

            if ($solicitud === null) {
                return;
            }

            $this->aLaPlataforma([
                'type' => 'plan.requested',
                'request_id' => $solicitud->id,
                'tenant_id' => $solicitud->tenant_id,
                'title' => 'Hay un cambio de plan esperando',
                'body' => sprintf('%s pidió cambiar de plan y espera revisión.', $this->nombreDeTienda($solicitud->tenant_id)),
                'url' => '/admin/backoffice/{recipient}/plan-changes',
            ]);
        });
    }

    public function planChangeResolved(string $requestId): void
    {
        $this->sinPropagar('plan.resolved', $requestId, function () use ($requestId) {
            $solicitud = TenantPlanChangeRequest::find($requestId);

            if ($solicitud === null) {
                return;
            }

            $aprobada = $solicitud->status === 'approved';

            /*
             * **La promesa que la aplicación llevaba haciendo desde el 23/08/2026.** La pantalla
             * responde «Solicitud enviada. Te avisaremos cuando la revisemos» y no había con qué.
             */
            $this->aLosDuenos($solicitud->tenant_id, [
                'type' => 'plan.resolved',
                'request_id' => $solicitud->id,
                'status' => $solicitud->status,
                'title' => $aprobada ? 'Tu cambio de plan se aprobó' : 'Tu cambio de plan no se aprobó',
                'body' => $aprobada
                    ? 'Ya está aplicado en tu suscripción.'
                    : 'Tu plan actual sigue igual.'.$this->motivo($solicitud->rejection_reason),
                'url' => '/tenant/owner/backoffice/{recipient}/billing',
            ], contexto: 'cambio de plan resuelto');
        });
    }

    // ---------------------------------------------------------------- Lo periódico

    public function claimAboutToExpire(string $claimId): void
    {
        $this->sinPropagar('claim.expiring', $claimId, function () use ($claimId) {
            $reclamacion = CustomerReturnRequest::find($claimId);

            if ($reclamacion === null || ! $reclamacion->isOpen()) {
                return;
            }

            /*
             * Freno por reclamación. El comando corre cada madrugada y el estado no cambia hasta
             * que alguien conteste, así que sin esto el mismo recordatorio saldría todos los días
             * — y un aviso repetido no avisa el doble: avisa menos.
             *
             * La ventana del freno es el plazo entero de respuesta: dentro de ese plazo, un
             * recordatorio es suficiente.
             */
            if (! $this->freno->shouldSend('claim-expiring:'.$claimId, now()->addDays($this->plazoDeRespuesta->days()))) {
                return;
            }

            $dias = $this->plazoDeRespuesta->daysLeftFrom($reclamacion->created_at);

            $this->aLosDuenos($reclamacion->tenant_id, [
                'type' => 'claim.expiring',
                'claim_id' => $reclamacion->id,
                'order_number' => $reclamacion->order_number,
                'days_left' => $dias,
                'title' => $dias === 0
                    ? 'Tu reclamación vence hoy'
                    : 'Te queda un día para responder una reclamación',
                'body' => sprintf(
                    'La reclamación de «%s» (pedido %s) se resolverá a favor del comprador si no respondes%s. Responder —aunque sea para rechazarla— lo evita.',
                    $reclamacion->product_name,
                    $reclamacion->order_number,
                    $dias === 0 ? ' hoy' : ' mañana',
                ),
                'url' => '/tenant/owner/backoffice/{recipient}/returns',
            ], contexto: 'reclamación a punto de vencer');
        });
    }

    public function coverageCeilingExceeded(): void
    {
        $this->sinPropagar('coverage.ceiling', 'mes-actual', function () {
            $mes = $this->cobertura->currentMonth();

            if (! $mes['over']) {
                return;
            }

            /*
             * Freno por MES, no por día. Una vez pasado el techo, el mes sigue pasado mañana y
             * pasado mañana: sin esto el aviso saldría cada madrugada hasta fin de mes y dejaría
             * de leerse justo cuando importa.
             *
             * La clave lleva el mes dentro, así que el aviso vuelve solo si el mes siguiente
             * también se pasa.
             */
            if (! $this->freno->shouldSend('coverage-ceiling:'.$mes['month'], now()->endOfMonth())) {
                return;
            }

            $this->aLaPlataforma([
                'type' => 'coverage.ceiling',
                'month' => $mes['month'],
                'spent_usd' => $mes['spent_usd'],
                'threshold_usd' => $mes['threshold_usd'],
                'title' => 'El gasto en coberturas pasó del techo',
                /*
                 * Se dice explícitamente que NO se ha cortado nada. El techo es una alarma, no un
                 * muro, y un aviso que no lo aclare hará que alguien salga corriendo a
                 * desbloquear pagos que nunca se bloquearon.
                 */
                'body' => sprintf(
                    'Este mes la plataforma lleva puestos $%s de su bolsillo, sobre un techo de $%s. No se ha cortado ningún pago: toca mirar qué lo causa.',
                    number_format($mes['spent_usd'], 2),
                    number_format($mes['threshold_usd'], 2),
                ),
                'url' => '/admin/backoffice/{recipient}/guarantee-rules',
            ]);
        });
    }

    // ---------------------------------------------------------------- Envío

    /**
     * @param  array<string, mixed>  $payload
     */
    private function aLosDuenos(string $tenantId, array $payload, string $contexto): void
    {
        $duenos = $this->destinatarios->owners($tenantId);

        if ($duenos->isEmpty()) {
            /*
             * Una tienda sin dueños es un problema de datos, no de notificaciones — pero si pasa
             * en silencio, nadie entenderá después por qué ese aviso nunca llegó.
             */
            Log::warning('Aviso sin destinatarios: la tienda no tiene dueños.', [
                'contexto' => $contexto,
                'tenant_id' => $tenantId,
            ]);

            return;
        }

        $this->enviar($duenos, $payload);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function aLaPlataforma(array $payload): void
    {
        $admins = $this->destinatarios->platformAdmins();

        if ($admins->isEmpty()) {
            Log::warning('Aviso sin destinatarios: no hay superadministradores activos.', [
                'tipo' => $payload['type'] ?? null,
            ]);

            return;
        }

        $this->enviar($admins, $payload);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function alComprador(?string $customerId, array $payload): void
    {
        if ($customerId === null) {
            return;
        }

        $comprador = CentralCustomer::find($customerId);

        if ($comprador !== null) {
            $comprador->notify(new InboxNotification($payload));
        }
    }

    /**
     * @param  Collection<int, object>  $destinatarios
     * @param  array<string, mixed>  $payload
     */
    private function enviar(Collection $destinatarios, array $payload): void
    {
        LaravelNotification::send($destinatarios, new InboxNotification($payload));
    }

    // ---------------------------------------------------------------- Auxiliares

    /**
     * Dónde se confirma una entrega, que depende de dónde se compró.
     *
     * Una compra del marketplace central se confirma en el portal; una de escaparate, en
     * `/mis-pedidos` **de esa tienda**, que vive en otro dominio. Un enlace al portal para un
     * pedido de tienda llevaría a una pantalla donde ese pedido no aparece.
     */
    private function dondeSeConfirma(OrderDeliveryConfirmation $expediente): string
    {
        if ($expediente->central_order_id !== null) {
            return '/account/orders';
        }

        $dominio = Tenant::with('domains')->find($expediente->tenant_id)?->domains->first()?->domain;

        // Mejor un aviso sin enlace que uno que lleva a ninguna parte.
        return $dominio === null ? '' : 'http://'.$dominio.'/mis-pedidos';
    }

    /** El nombre de la tienda, o su identificador si no se puede leer. */
    private function nombreDeTienda(string $tenantId): string
    {
        return (string) (Tenant::where('id', $tenantId)->value('name') ?? 'Una tienda');
    }

    private function importe(CommissionSettlement $retiro): string
    {
        return number_format((float) $retiro->net_amount, 2).' '.($retiro->currency ?: 'USD');
    }

    /** El motivo, si lo hay, como frase añadida. Sin motivo no se escribe nada. */
    private function motivo(?string $texto): string
    {
        $limpio = trim((string) $texto);

        return $limpio === '' ? '' : ' Motivo: «'.$limpio.'».';
    }

    /** El cortafuegos. Todo lo que pase dentro se registra y muere aquí. */
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
