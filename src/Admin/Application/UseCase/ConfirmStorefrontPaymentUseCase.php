<?php

declare(strict_types=1);

namespace Src\Admin\Application\UseCase;

use Exception;
use Illuminate\Support\Facades\DB;
use Src\Monetization\Application\UseCases\ActivateOrderCommissionUseCase;
use Src\Monetization\Infrastructure\Eloquent\Models\PlatformCommission;
use Src\Order\Application\Contracts\Repositories\OrderRepositoryInterface;
use Src\Order\Domain\ValueObjects\OrderId;
use Src\Tenant\Infrastructure\Eloquent\Models\Tenant;

/**
 * Confirma que el dinero de una venta del escaparate entró en la cuenta de la plataforma.
 *
 * **Por qué existe.** Desde que la plataforma cobra todas las ventas, el comerciante ya no
 * puede decir si el dinero llegó: no tiene acceso a ese extracto bancario. El que cobra es el
 * que confirma.
 *
 * **Por qué la lista sale de `platform_commissions`.** Los pedidos del escaparate viven en la
 * base de cada tienda, así que no hay ninguna consulta central que los vea. Pero la comisión
 * sí es central, se escribe en cada venta, y una en `awaiting_payment` **es** un cobro
 * pendiente. La proyección ya existía; sólo le faltaba la referencia del comprador, que ahora
 * viaja en su `metadata`.
 *
 * Ni tabla nueva ni recorrer las bases de los inquilinos: dos copias de lo mismo divergen, y
 * recorrer inquilinos no escala más allá de unas pocas tiendas.
 */
final class ConfirmStorefrontPaymentUseCase
{
    public function __construct(
        private readonly ActivateOrderCommissionUseCase $activateCommission
    ) {}

    /**
     * @return array{commission: PlatformCommission, commissions_activated: int}
     */
    public function execute(string $commissionId, string $adminUserId, ?string $reference = null, ?string $notes = null): array
    {
        $commission = PlatformCommission::find($commissionId);

        if (! $commission) {
            throw new Exception("Cobro '{$commissionId}' no encontrado.", 404);
        }

        // Se rechaza en vez de aceptar y no hacer nada: confirmar dos veces mentiría sobre
        // cuándo entró el dinero, y esa fecha es la que sostiene cualquier reclamación.
        if ($commission->status !== 'awaiting_payment') {
            throw new Exception(
                "El cobro del pedido '{$commission->order_number}' no está pendiente: su comisión está en '{$commission->status}'.",
                422
            );
        }

        $metadata = $commission->metadata ?? [];
        $metadata['payment_confirmation'] = [
            'confirmed_by' => $adminUserId,
            // Se guarda junto a la que puso el comprador, no encima: si no coinciden, eso es
            // justamente lo que hay que poder ver después.
            'reference' => $reference,
            'notes' => $notes,
            'confirmed_at' => now()->toIso8601String(),
        ];
        $commission->metadata = $metadata;
        $commission->save();

        $tenant = Tenant::find($commission->tenant_id);

        if ($tenant !== null) {
            try {
                tenancy()->initialize($tenant);

                $repository = app(OrderRepositoryInterface::class);
                $order = $repository->findById(new OrderId((string) $commission->order_id));

                // Por la entidad y no con un `update()` a pelo, que es el hallazgo SH1. Y se
                // pregunta antes: un pedido ya pagado no es un error, es que no hay nada que
                // hacer.
                if ($order !== null && $order->paymentStatus()->canBePaid()) {
                    $order->markPaymentPaid();
                    $repository->save($order);
                }

                DB::table('payments')
                    ->where('order_id', $commission->order_id)
                    ->update([
                        'status' => 'completed',
                        'paid_at' => now(),
                        'updated_at' => now(),
                    ]);
            } finally {
                if (tenancy()->initialized) {
                    tenancy()->end();
                }
            }
        }

        // Fuera de la tenancy a proposito, como en la confirmacion del cobro central:
        // `PlatformCommission` fija su conexion a la central, pero dejarlo aqui hace evidente
        // sobre que base escribe.
        $activadas = $this->activateCommission->execute((string) $commission->order_id);

        return [
            'commission' => $commission->fresh(),
            'commissions_activated' => $activadas,
        ];
    }
}
