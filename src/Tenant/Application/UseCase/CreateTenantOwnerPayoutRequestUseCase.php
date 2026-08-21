<?php

declare(strict_types=1);

namespace Src\Tenant\Application\UseCase;

use Src\Monetization\Infrastructure\Eloquent\Models\CommissionSettlement;
use Src\Monetization\Infrastructure\Eloquent\Models\PlatformCommission;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Src\Tenant\Application\Service\TenantOwnershipVerifier;

final class CreateTenantOwnerPayoutRequestUseCase
{
    public function __construct(
        private readonly TenantOwnershipVerifier $ownership
    ) {}

    /**
     * @param array{
     *     tenant_id: string,
     *     amount: float,
     *     payment_method: string,
     *     payment_details: array<string, mixed>,
     *     notes?: string|null
     * } $data
     */
    public function execute(string $userId, array $data): CommissionSettlement
    {
        // 1. El solicitante debe ser propietario de la tienda. Lanza 404 si no existe
        //    y 403 si existe pero es de otro comerciante.
        $this->ownership->ensureOwns($userId, $data['tenant_id']);

        if ($data['amount'] <= 0) {
            throw new Exception('El monto a retirar debe ser mayor a 0.', 422);
        }

        return DB::transaction(function () use ($userId, $data) {
            // 2. El importe solicitado no puede superar el saldo disponible de la tienda.
            //    Se recalcula dentro de la transacción para no partir de una lectura vieja.
            $availableBalance = $this->availableBalanceFor($data['tenant_id']);

            if ($data['amount'] > $availableBalance) {
                throw new Exception(
                    sprintf(
                        'El monto solicitado (%.2f USD) supera tu saldo disponible (%.2f USD).',
                        $data['amount'],
                        $availableBalance
                    ),
                    422
                );
            }

            $settlementNumber = 'PAY-'.date('Ymd').'-'.strtoupper(Str::random(6));

            return CommissionSettlement::create([
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
        });
    }

    /**
     * Saldo retirable de una tienda: ventas netas de comisión, menos lo ya liquidado
     * y lo que hay pendiente de liquidar.
     */
    private function availableBalanceFor(string $tenantId): float
    {
        $grossSales = 0.0;
        $totalCommissions = 0.0;
        $settledPayouts = 0.0;
        $pendingPayouts = 0.0;

        if (Schema::hasTable('platform_commissions')) {
            $grossSales = (float) PlatformCommission::where('tenant_id', $tenantId)->sum('order_amount');
            $totalCommissions = (float) PlatformCommission::where('tenant_id', $tenantId)->sum('commission_amount');
        }

        if (Schema::hasTable('commission_settlements')) {
            $settledPayouts = (float) CommissionSettlement::where('tenant_id', $tenantId)
                ->where('type', 'payout')
                ->where('status', 'settled')
                ->sum('net_amount');

            $pendingPayouts = (float) CommissionSettlement::where('tenant_id', $tenantId)
                ->where('type', 'payout')
                ->where('status', 'pending')
                ->sum('net_amount');
        }

        $netEarnings = max(0.0, $grossSales - $totalCommissions);

        return max(0.0, $netEarnings - $settledPayouts - $pendingPayouts);
    }
}
