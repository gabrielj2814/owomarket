<?php

declare(strict_types=1);

namespace Src\Tenant\Application\UseCase;

use App\Models\CentralOrder;
use App\Models\CommissionSettlement;
use App\Models\PlatformCommission;
use Illuminate\Support\Facades\Schema;
use Src\Tenant\Infrastructure\Eloquent\Models\Tenant;

final class GetTenantOwnerWalletSummaryUseCase
{
    /**
     * @return array<string, mixed>
     */
    public function execute(string $userId): array
    {
        // 1. Obtener los tenants del usuario
        $tenants = Tenant::whereHas('users', function ($q) use ($userId) {
            $q->where('user_id', $userId);
        })->get();

        if ($tenants->isEmpty()) {
            $tenants = Tenant::where('status', 'active')->limit(5)->get();
        }

        $tenantIds = $tenants->pluck('id')->toArray();

        $grossSales = 0.0;
        $totalCommissions = 0.0;
        $settledPayouts = 0.0;
        $pendingPayouts = 0.0;

        if (Schema::hasTable('platform_commissions')) {
            $grossSales = (float) PlatformCommission::whereIn('tenant_id', $tenantIds)->sum('order_amount');
            $totalCommissions = (float) PlatformCommission::whereIn('tenant_id', $tenantIds)->sum('commission_amount');
        }

        if (Schema::hasTable('commission_settlements')) {
            $settledPayouts = (float) CommissionSettlement::whereIn('tenant_id', $tenantIds)
                ->where('type', 'payout')
                ->where('status', 'settled')
                ->sum('net_amount');

            $pendingPayouts = (float) CommissionSettlement::whereIn('tenant_id', $tenantIds)
                ->where('type', 'payout')
                ->where('status', 'pending')
                ->sum('net_amount');
        }

        $netEarnings = max(0.0, $grossSales - $totalCommissions);
        $availableBalance = max(0.0, $netEarnings - $settledPayouts - $pendingPayouts);

        $bcvRate = 775.3356;

        // Historial de liquidaciones recientes
        $settlements = [];
        if (Schema::hasTable('commission_settlements')) {
            $settlements = CommissionSettlement::whereIn('tenant_id', $tenantIds)
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get()
                ->map(function ($s) use ($bcvRate) {
                    return [
                        'id' => $s->id,
                        'settlement_number' => $s->settlement_number,
                        'tenant_id' => $s->tenant_id,
                        'type' => $s->type,
                        'amount_usd' => (float) $s->net_amount,
                        'amount_ves' => round((float) $s->net_amount * $bcvRate, 2),
                        'status' => $s->status,
                        'payment_method' => $s->payment_method ?? 'Pago Móvil',
                        'payment_reference' => $s->payment_reference,
                        'date' => $s->created_at?->format('d/m/Y H:i'),
                    ];
                })
                ->toArray();
        }

        return [
            'gross_sales' => $grossSales,
            'total_commissions' => $totalCommissions,
            'available_balance' => $availableBalance,
            'available_balance_ves' => round($availableBalance * $bcvRate, 2),
            'pending_payouts' => $pendingPayouts,
            'settled_payouts' => $settledPayouts,
            'bcv_rate' => $bcvRate,
            'tenants_count' => count($tenantIds),
            'settlements' => $settlements,
        ];
    }
}
