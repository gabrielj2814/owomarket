<?php

declare(strict_types=1);

namespace Src\Admin\Application\UseCase;

use Exception;
use Src\CentralMarketplace\Infrastructure\Eloquent\Models\CentralOrderDispatch;
use Src\Monetization\Application\UseCases\ReverseOrderCommissionUseCase;
use Src\Order\Infrastructure\Eloquent\Models\CentralOrder;

final class ResolveCentralOrderDisputeUseCase
{
    public function __construct(
        private readonly ReverseOrderCommissionUseCase $reverseCommission
    ) {}

    /**
     * @param array{
     *     resolution_type: 'refund'|'cancel',
     *     reason: string,
     *     notes?: string|null
     * } $data
     */
    public function execute(string $orderId, string $adminUserId, array $data): CentralOrder
    {
        $order = CentralOrder::find($orderId);

        if (! $order) {
            throw new Exception("Orden '{$orderId}' no encontrada.", 404);
        }

        $resolutionType = $data['resolution_type'];
        $reason = $data['reason'];

        $metadata = $order->metadata ?? [];
        $metadata['dispute_resolution'] = [
            'resolved_by' => $adminUserId,
            'resolution_type' => $resolutionType,
            'reason' => $reason,
            'notes' => $data['notes'] ?? null,
            'resolved_at' => now()->toIso8601String(),
        ];

        if ($resolutionType === 'refund') {
            $order->status = 'refunded';
            $order->payment_status = 'refunded';
        } else {
            $order->status = 'cancelled';
            $order->payment_status = 'cancelled';
        }

        $order->metadata = $metadata;
        $order->save();

        // Hallazgo C: esto era `PlatformCommission::where('order_id', $order->id)`, y
        // `$order->id` es el pedido CENTRAL. Las comisiones guardan el id del pedido de la
        // TIENDA en `order_id` y el central en su propia columna --justo lo que separo el
        // hallazgo Auditoria #1--, asi que ese `where` no casaba con ninguna fila: se
        // reembolsaba al comprador y la plataforma se quedaba la comision. El mismo fallo
        // que D2 cerro para los pedidos de tienda, vivo en el camino central.
        //
        // Ahora se recorren los despachos, que son los que saben el id de cada pedido de
        // tienda, y se usa `ReverseOrderCommissionUseCase`, que ya hace la reversion bien:
        // contempla `awaiting_payment` (N15) y marca para ajuste manual lo ya liquidado.
        $reason = $resolutionType === 'refund'
            ? ReverseOrderCommissionUseCase::REASON_REFUNDED
            : ReverseOrderCommissionUseCase::REASON_CANCELLED;

        $dispatches = CentralOrderDispatch::where('central_order_id', $order->id)
            ->whereNotNull('tenant_order_id')
            ->get();

        // Sin `catch` vacio: antes esto iba dentro de un `// Silently handle`, asi que ni
        // siquiera habria avisado. Una comision que sobrevive a un reembolso es dinero.
        foreach ($dispatches as $dispatch) {
            $this->reverseCommission->execute(
                (string) $dispatch->tenant_order_id,
                $reason,
                $data['notes'] ?? $reason
            );
        }

        return $order;
    }
}
