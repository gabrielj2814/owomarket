<?php

declare(strict_types=1);

namespace Src\Admin\Application\UseCase;

use Exception;
use Illuminate\Support\Facades\DB;
use Src\Monetization\Application\Service\TenantAvailableBalance;
use Src\Monetization\Infrastructure\Eloquent\Models\CommissionSettlement;

final class ApproveCentralPayoutRequestUseCase
{
    public function __construct(
        private readonly TenantAvailableBalance $balance
    ) {}

    /**
     * @param array{
     *     payment_reference: string,
     *     notes?: string|null
     * } $data
     */
    public function execute(string $settlementId, string $adminUserId, array $data): CommissionSettlement
    {
        return DB::transaction(function () use ($settlementId, $adminUserId, $data) {
            return $this->aprobar($settlementId, $adminUserId, $data);
        });
    }

    /**
     * @param  array{payment_reference: string, notes?: string|null}  $data
     */
    private function aprobar(string $settlementId, string $adminUserId, array $data): CommissionSettlement
    {
        $settlement = CommissionSettlement::where('type', 'payout')->find($settlementId);

        if (! $settlement) {
            throw new Exception('Solicitud de retiro no encontrada.', 404);
        }

        if ($settlement->status !== 'pending') {
            throw new Exception("No se puede aprobar una solicitud en estado '{$settlement->status}'.", 400);
        }

        if (empty($data['payment_reference'])) {
            throw new Exception('El número de referencia bancaria o TXID de Binance es obligatorio.', 422);
        }

        /*
         * Hallazgo T1: aqui es donde el dinero sale, y hasta ahora no se miraba el saldo.
         * Se comprobaba al PEDIR el retiro y nunca mas, asi que cualquier cosa que redujera
         * las ganancias entre la solicitud y la aprobacion —una devolucion, un ajuste de
         * comision, o simplemente dos solicitudes creadas a la vez sin bloqueo— acababa en
         * un pago sin respaldo.
         *
         * Se compara contra `settleable`, que NO descuenta los retiros pendientes: si los
         * descontara, esta misma solicitud estaria restada de su propio respaldo. Al
         * aprobar en orden, cada una pasa a 'settled' y reduce el saldo de la siguiente.
         */
        $disponible = $this->balance->settleable((string) $settlement->tenant_id, lock: true);

        if ((float) $settlement->net_amount > $disponible) {
            throw new Exception(
                sprintf(
                    'El retiro (%.2f USD) supera el saldo disponible de la tienda (%.2f USD). El saldo cambió desde que se solicitó.',
                    (float) $settlement->net_amount,
                    $disponible
                ),
                422
            );
        }

        $metadata = $settlement->metadata ?? [];
        $metadata['approved_by'] = $adminUserId;
        $metadata['approved_at'] = now()->toIso8601String();
        if (! empty($data['notes'])) {
            $metadata['admin_notes'] = $data['notes'];
        }

        $settlement->update([
            'status' => 'settled',
            'payment_reference' => $data['payment_reference'],
            'settled_at' => now(),
            'notes' => $data['notes'] ?? $settlement->notes,
            'metadata' => $metadata,
        ]);

        return $settlement;
    }
}
