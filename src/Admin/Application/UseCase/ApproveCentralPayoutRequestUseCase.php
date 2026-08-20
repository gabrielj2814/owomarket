<?php

declare(strict_types=1);

namespace Src\Admin\Application\UseCase;

use App\Models\CommissionSettlement;
use Exception;

final class ApproveCentralPayoutRequestUseCase
{
    /**
     * @param array{
     *     payment_reference: string,
     *     notes?: string|null
     * } $data
     */
    public function execute(string $settlementId, string $adminUserId, array $data): CommissionSettlement
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
