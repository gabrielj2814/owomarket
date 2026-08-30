<?php

declare(strict_types=1);

namespace Src\Admin\Application\UseCase;

use Exception;
use Illuminate\Support\Facades\DB;
use Src\CentralMarketplace\Infrastructure\Eloquent\Models\CentralOrderDispatch;
use Src\Monetization\Application\UseCases\ActivateOrderCommissionUseCase;
use Src\Order\Application\Contracts\Repositories\OrderRepositoryInterface;
use Src\Order\Domain\ValueObjects\OrderId;
use Src\Order\Infrastructure\Eloquent\Models\CentralOrder;
use Src\Tenant\Infrastructure\Eloquent\Models\Tenant;

/**
 * Hallazgo A: confirmar el cobro de un pedido central no existia.
 *
 * `central_orders.payment_status` nacia en `pending` y las unicas dos lineas que lo
 * reescribian eran la resolucion de disputas, a `refunded` y `cancelled`. Ningun camino lo
 * ponia en `paid`: ni endpoint, ni accion de admin, ni webhook. La columna se lee en seis
 * sitios y no la escribia nadie.
 *
 * Lo grave no eran las metricas. `GenerateTenantCommissionSettlementUseCase` genera las dos
 * direcciones --`collection`, la tienda paga su comision, y `payout`, la plataforma paga a la
 * tienda-- leyendo ambas comisiones en `pending`. Una comision atascada en
 * `awaiting_payment` bloquea tambien el `payout`, y en el marketplace central el comprador le
 * paga a la PLATAFORMA: sin esta confirmacion el dinero no llegaba nunca al comerciante.
 *
 * La regla de N15 no cambia: la comision sigue naciendo devengada y no cobrable. Lo que se
 * añade es quien puede confirmar el cobro cuando el dinero entra en la plataforma.
 */
final class ConfirmCentralOrderPaymentUseCase
{
    public function __construct(
        private readonly ActivateOrderCommissionUseCase $activateCommission
    ) {}

    /**
     * @return array{order: CentralOrder, tenant_orders: list<string>, commissions_activated: int}
     */
    public function execute(string $centralOrderId, string $adminUserId, ?string $reference = null, ?string $notes = null): array
    {
        $order = CentralOrder::find($centralOrderId);

        if (! $order) {
            throw new Exception("Orden central '{$centralOrderId}' no encontrada.", 404);
        }

        // Se rechaza en vez de aceptar y no hacer nada. Es el fallo de PR2, OR1 y SH1, y
        // aqui confirmar dos veces ademas mentiria sobre cuando entro el dinero.
        if ($order->payment_status !== 'pending') {
            throw new Exception(
                "El cobro de la orden '{$order->order_number}' no se puede confirmar: su pago está en '{$order->payment_status}'.",
                422
            );
        }

        $metadata = $order->metadata ?? [];
        $metadata['payment_confirmation'] = [
            'confirmed_by' => $adminUserId,
            // La referencia que el admin coteja contra el banco. Se guarda junto a la que
            // envio el comprador (`payment_details`), no encima: si no coinciden, eso es
            // justamente lo que hay que poder ver despues.
            'reference' => $reference,
            'notes' => $notes,
            'confirmed_at' => now()->toIso8601String(),
        ];

        $order->payment_status = 'paid';
        $order->metadata = $metadata;
        $order->save();

        // Si el despacho todavia no ha corrido no hay nada que hacer aqui: el propio
        // `DispatchCentralOrderToTenantsUseCase` lee `payment_status === 'paid'` y crea la
        // comision ya cobrable y la fila de `payments` en `completed`. Los dos ordenes de
        // llegada convergen, y hay un test que lo fija.
        $tenantOrderIds = [];

        $dispatches = CentralOrderDispatch::where('central_order_id', $order->id)
            ->where('status', 'dispatched')
            ->whereNotNull('tenant_order_id')
            ->get();

        foreach ($dispatches as $dispatch) {
            $tenant = Tenant::find($dispatch->tenant_id);
            if (! $tenant) {
                continue;
            }

            try {
                tenancy()->initialize($tenant);

                $repository = app(OrderRepositoryInterface::class);
                $tenantOrder = $repository->findById(new OrderId((string) $dispatch->tenant_order_id));

                if ($tenantOrder === null) {
                    continue;
                }

                // Por la entidad, no con un `update()` a pelo: SH1 fue exactamente eso.
                // Y se pregunta antes en vez de capturar la excepcion, porque un pedido
                // que ya estaba pagado no es un error, es que no hay nada que hacer.
                if ($tenantOrder->paymentStatus()->canBePaid()) {
                    $tenantOrder->markPaymentPaid();
                    $repository->save($tenantOrder);
                }

                DB::table('payments')
                    ->where('order_id', $dispatch->tenant_order_id)
                    ->update([
                        'status' => 'completed',
                        'paid_at' => now(),
                        'updated_at' => now(),
                    ]);

                $tenantOrderIds[] = (string) $dispatch->tenant_order_id;
            } finally {
                if (tenancy()->initialized) {
                    tenancy()->end();
                }
            }
        }

        // Fuera de la tenancy a proposito. `PlatformCommission` fija su conexion a la
        // central, pero dejar la promocion aqui hace evidente sobre que base escribe.
        $activated = 0;
        foreach ($tenantOrderIds as $tenantOrderId) {
            $activated += $this->activateCommission->execute($tenantOrderId);
        }

        return [
            'order' => $order->fresh(),
            'tenant_orders' => $tenantOrderIds,
            'commissions_activated' => $activated,
        ];
    }
}
