<?php

declare(strict_types=1);

namespace Src\Tenant\Application\UseCase;

use Src\Monetization\Infrastructure\Eloquent\Models\CommissionSettlement;
use Exception;
use Illuminate\Support\Str;
use Src\Tenant\Infrastructure\Eloquent\Models\Tenant;

final class CreateTenantOwnerPayoutRequestUseCase
{
    /**
     * @param array{
     *     tenant_id: string,
     *     amount: float,
     *     payment_method: string,
     *     payment_details: array<string, mixed>,
     *     notes?: string|null
     * } $data
     * @return CommissionSettlement
     */
    public function execute(string $userId, array $data): CommissionSettlement
    {
        $tenant = Tenant::find($data['tenant_id']);
        if (! $tenant) {
            throw new Exception('La tienda especificada no existe.', 404);
        }

        if ($data['amount'] <= 0) {
            throw new Exception('El monto a retirar debe ser mayor a 0.', 422);
        }

        $settlementNumber = 'PAY-'.date('Ymd').'-'.strtoupper(Str::random(6));

        $settlement = CommissionSettlement::create([
            'id' => (string) Str::uuid(),
            'settlement_number' => $settlementNumber,
            'tenant_id' => $data['tenant_id'],
            'type' => 'payout',
            'gross_sales_amount' => $data['amount'],
            'commission_amount' => 0.00,
            'net_amount' => $data['amount'],
            'currency' => 'USD',
            'status' => 'pending',
            'payment_method' => $data['payment_method'],
            'notes' => $data['notes'] ?? 'Solicitud de retiro generada desde la Billetera Central',
            'metadata' => [
                'user_id' => $userId,
                'payment_details' => $data['payment_details'],
                'requested_at' => now()->toIso8601String(),
            ],
        ]);

        return $settlement;
    }
}
