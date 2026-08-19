<?php

declare(strict_types=1);

namespace Src\Monetization\Application\UseCases;

use App\Models\CommissionSettlement;
use App\Models\PlatformCommission;
use App\Models\SubscriptionPlan;
use App\Models\TenantSubscription;
use Src\Tenant\Infrastructure\Eloquent\Models\Tenant;

final class GetSuperAdminMonetizationMetricsUseCase
{
    /**
     * @return array<string, mixed>
     */
    public function execute(): array
    {
        $totalTenants = Tenant::count();

        // Platform Commissions
        $totalSalesVolume = (float) PlatformCommission::sum('order_total');
        $totalCommissionsGenerated = (float) PlatformCommission::sum('commission_amount');
        $totalCommissionsCollected = (float) PlatformCommission::where('status', 'collected')->sum('commission_amount');
        $totalCommissionsPending = (float) PlatformCommission::where('status', 'pending')->sum('commission_amount');

        // Settlements
        $totalSettlements = CommissionSettlement::count();
        $pendingSettlements = CommissionSettlement::where('status', 'pending')->count();
        $settledAmount = (float) CommissionSettlement::where('status', 'settled')->sum('commission_amount');

        // Subscription Plans and Active Subscriptions
        $plans = SubscriptionPlan::where('is_active', true)->get();
        $activeSubscriptions = TenantSubscription::where('status', 'active')->count();

        $subscriptionsByPlan = [];
        foreach ($plans as $plan) {
            $count = TenantSubscription::where('plan_id', $plan->id)->where('status', 'active')->count();
            $subscriptionsByPlan[] = [
                'plan_name' => $plan->name,
                'slug' => $plan->slug,
                'price_monthly' => (float) $plan->price_monthly,
                'commission_rate' => (float) $plan->commission_rate,
                'active_subscribers' => $count,
            ];
        }

        return [
            'platform' => [
                'total_tenants' => $totalTenants,
                'total_sales_volume' => $totalSalesVolume,
                'total_commissions_generated' => $totalCommissionsGenerated,
                'total_commissions_collected' => $totalCommissionsCollected,
                'total_commissions_pending' => $totalCommissionsPending,
                'currency' => 'USD',
            ],
            'settlements' => [
                'total_settlements_count' => $totalSettlements,
                'pending_settlements_count' => $pendingSettlements,
                'settled_amount' => $settledAmount,
            ],
            'subscriptions' => [
                'total_active_subscriptions' => $activeSubscriptions,
                'plans_breakdown' => $subscriptionsByPlan,
            ],
        ];
    }
}
