<?php

declare(strict_types=1);

namespace Src\Monetization\Application\UseCases;

use App\Models\CommissionSettlement;
use App\Models\PlatformCommission;
use Exception;
use Illuminate\Support\Str;
use Src\Tenant\Infrastructure\Eloquent\Models\Tenant;

final class GenerateTenantCommissionSettlementUseCase
{
    /**
     * @param string $tenantId
     * @param string $type 'collection' | 'payout'
     * @param string|null $notes
     * @return CommissionSettlement
     */
    public function execute(string $tenantId, string $type = 'collection', ?string $notes = null): CommissionSettlement
    {
        $tenant = Tenant::find($tenantId);
        if (! $tenant) {
            throw new Exception('Inquilino/Tienda no encontrado.', 404);
        }

        $pendingCommissions = PlatformCommission::where('tenant_id', $tenantId)
            ->where('status', 'pending')
            ->whereNull('settlement_id')
            ->get();

        if ($pendingCommissions->isEmpty()) {
            throw new Exception('No hay comisiones pendientes de liquidación para esta tienda.', 422);
        }

        $settlementNumber = 'SET-'.date('Ym').'-'.strtoupper(Str::random(6));

        $totalOrdersCount = $pendingCommissions->count();
        $grossSalesAmount = (float) $pendingCommissions->sum('order_total');
        $commissionAmount = (float) $pendingCommissions->sum('commission_amount');
        $netAmount = $type === 'collection' ? $commissionAmount : max(0.0, $grossSalesAmount - $commissionAmount);

        $settlement = CommissionSettlement::create([
            'id' => (string) Str::uuid(),
            'settlement_number' => $settlementNumber,
            'tenant_id' => $tenantId,
            'type' => in_array($type, ['collection', 'payout'], true) ? $type : 'collection',
            'total_orders_count' => $totalOrdersCount,
            'gross_sales_amount' => $grossSalesAmount,
            'commission_amount' => $commissionAmount,
            'net_amount' => $netAmount,
            'currency' => 'USD',
            'status' => 'pending',
            'notes' => $notes,
            'metadata' => [
                'generated_at' => now()->toIso8601String(),
                'orders_breakdown' => $pendingCommissions->pluck('order_number')->toArray(),
            ],
        ]);

        // Link pending commissions to this settlement
        PlatformCommission::whereIn('id', $pendingCommissions->pluck('id'))
            ->update(['settlement_id' => $settlement->id]);

        return $settlement->fresh(['commissions', 'tenant']);
    }
}
