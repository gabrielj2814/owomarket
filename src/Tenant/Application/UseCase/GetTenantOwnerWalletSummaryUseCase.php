<?php

declare(strict_types=1);

namespace Src\Tenant\Application\UseCase;

use Src\Monetization\Infrastructure\Eloquent\Models\CommissionSettlement;
use Src\Monetization\Infrastructure\Eloquent\Models\PlatformCommission;
use Illuminate\Support\Facades\Schema;
use Src\Tenant\Application\Service\TenantOwnershipVerifier;

final class GetTenantOwnerWalletSummaryUseCase
{
    public function __construct(
        private readonly TenantOwnershipVerifier $ownership
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function execute(string $userId): array
    {
        // Sólo las tiendas del propio usuario. Si no tiene ninguna, el resumen va en cero:
        // NUNCA se cae hacia las tiendas de otros comerciantes.
        $tenants = $this->ownership->tenantsOf($userId);
        $tenantIds = $tenants->pluck('id')->map(fn ($id) => (string) $id)->all();

        $grossSales = 0.0;
        $totalCommissions = 0.0;
        $settledPayouts = 0.0;
        $pendingPayouts = 0.0;
        $settlements = [];

        // TODO(Fase 1): la tasa está fijada en código, igual que en el frontend.
        // Debe venir de Src\ExchangeRate (hallazgo D3/G13 de la auditoría).
        $bcvRate = 775.3356;

        if ($tenantIds !== []) {
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
        }

        $netEarnings = max(0.0, $grossSales - $totalCommissions);
        $availableBalance = max(0.0, $netEarnings - $settledPayouts - $pendingPayouts);

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
