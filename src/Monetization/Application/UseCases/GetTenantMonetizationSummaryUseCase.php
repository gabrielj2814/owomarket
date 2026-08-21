<?php

declare(strict_types=1);

namespace Src\Monetization\Application\UseCases;

use Src\Monetization\Infrastructure\Eloquent\Models\PlatformCommission;
use Src\Monetization\Infrastructure\Eloquent\Models\TenantSubscription;
use Src\Tenant\Infrastructure\Eloquent\Models\Tenant;

final class GetTenantMonetizationSummaryUseCase
{
    public function __construct(
        private readonly CalculateAndRecordOrderCommissionUseCase $commissionHelper
    ) {}

    /**
     * @param string $tenantId
     * @return array<string, mixed>
     */
    public function execute(string $tenantId): array
    {
        $tenant = Tenant::find($tenantId);

        // Subscription
        $subscription = TenantSubscription::with('plan')
            ->where('tenant_id', $tenantId)
            ->where('status', 'active')
            ->first();

        // Effective Rate
        $currentRate = $this->commissionHelper->resolveCommissionRate($tenantId);

        // Aggregate Commissions
        $commissionsQuery = PlatformCommission::where('tenant_id', $tenantId);
        $totalOrdersCount = (clone $commissionsQuery)->count();
        $totalVolumeSold = (float) (clone $commissionsQuery)->sum('order_total');
        $totalCommissions = (float) (clone $commissionsQuery)->sum('commission_amount');
        $pendingCommissions = (float) (clone $commissionsQuery)->where('status', 'pending')->sum('commission_amount');
        $collectedCommissions = (float) (clone $commissionsQuery)->where('status', 'collected')->sum('commission_amount');

        return [
            'tenant_id' => $tenantId,
            'plan' => $subscription && $subscription->plan ? [
                'name' => $subscription->plan->name,
                'slug' => $subscription->plan->slug,
                'billing_cycle' => $subscription->billing_cycle,
                'starts_at' => $subscription->starts_at?->toIso8601String(),
                'ends_at' => $subscription->ends_at?->toIso8601String(),
            ] : [
                'name' => 'Plan Gratuito / Básico',
                'slug' => 'free',
                'billing_cycle' => 'none',
                'starts_at' => null,
                'ends_at' => null,
            ],
            'effective_commission_rate' => $currentRate,
            'is_custom_rate' => ($tenant->custom_commission_rate ?? ($tenant->data['custom_commission_rate'] ?? null)) !== null,
            'metrics' => [
                'total_orders' => $totalOrdersCount,
                'total_volume_sold' => $totalVolumeSold,
                'total_commissions' => $totalCommissions,
                'pending_commissions' => $pendingCommissions,
                'collected_commissions' => $collectedCommissions,
                'currency' => 'USD',
            ],
        ];
    }
}
