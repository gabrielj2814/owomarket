<?php

declare(strict_types=1);

namespace Src\Monetization\Application\UseCases;

use Src\Monetization\Infrastructure\Eloquent\Models\CommissionSettlement;
use Src\Monetization\Infrastructure\Eloquent\Models\PlatformCommission;
use Exception;

final class ConfirmAndSettleCommissionUseCase
{
    /**
     * @param string $settlementId
     * @param string|null $paymentMethod 'pago_movil' | 'binance_pay' | 'bank_transfer'
     * @param string|null $paymentReference
     * @param string|null $notes
     * @return CommissionSettlement
     */
    public function execute(
        string $settlementId,
        ?string $paymentMethod = null,
        ?string $paymentReference = null,
        ?string $notes = null
    ): CommissionSettlement {
        $settlement = CommissionSettlement::with('commissions')->find($settlementId);

        if (! $settlement) {
            throw new Exception('Liquidación de comisiones no encontrada.', 404);
        }

        if ($settlement->status === 'settled') {
            throw new Exception('Esta liquidación ya ha sido cobrada y conciliada previamente.', 422);
        }

        $settlement->status = 'settled';
        $settlement->payment_method = $paymentMethod ?? $settlement->payment_method;
        $settlement->payment_reference = $paymentReference ?? $settlement->payment_reference;
        $settlement->settled_at = now();
        if ($notes) {
            $settlement->notes = $settlement->notes ? "{$settlement->notes} | {$notes}" : $notes;
        }
        $settlement->save();

        // Mark all associated platform commissions as collected
        PlatformCommission::where('settlement_id', $settlement->id)
            ->update([
                'status' => 'collected',
            ]);

        return $settlement->fresh(['commissions', 'tenant']);
    }
}
