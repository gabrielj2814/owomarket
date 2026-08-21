<?php

declare(strict_types=1);

namespace Src\Admin\Application\UseCase;

use Src\Monetization\Infrastructure\Eloquent\Models\CommissionSettlement;
use Exception;

final class RejectCentralPayoutRequestUseCase
{
    /**
     * @param array{
     *     rejection_reason: string,
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
            throw new Exception("No se puede rechazar una solicitud en estado '{$settlement->status}'.", 400);
        }

        if (empty($data['rejection_reason'])) {
            throw new Exception('El motivo de rechazo es obligatorio.', 422);
        }

        $metadata = $settlement->metadata ?? [];
        $metadata['rejected_by'] = $adminUserId;
        $metadata['rejected_at'] = now()->toIso8601String();
        $metadata['rejection_reason'] = $data['rejection_reason'];

        $settlement->update([
            'status' => 'cancelled',
            'notes' => $data['rejection_reason'],
            'metadata' => $metadata,
        ]);

        return $settlement;
    }
}
