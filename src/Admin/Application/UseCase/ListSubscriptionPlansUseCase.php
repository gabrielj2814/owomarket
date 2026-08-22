<?php

declare(strict_types=1);

namespace Src\Admin\Application\UseCase;

use Illuminate\Database\Eloquent\Collection;
use Src\Monetization\Infrastructure\Eloquent\Models\SubscriptionPlan;
use Src\Monetization\Infrastructure\Eloquent\Models\TenantSubscription;

final class ListSubscriptionPlansUseCase
{
    /**
     * @return array{
     *     plans: Collection,
     *     metrics: array{
     *         total_plans: int,
     *         active_plans: int,
     *         active_subscriptions: int
     *     }
     * }
     */
    public function execute(): array
    {
        $plans = SubscriptionPlan::withCount('subscriptions')->orderBy('price_monthly', 'asc')->get();

        $totalPlans = SubscriptionPlan::count();
        $activePlans = SubscriptionPlan::where('is_active', true)->count();
        $activeSubs = TenantSubscription::where('status', 'active')->count();

        return [
            'plans' => $plans,
            'metrics' => [
                'total_plans' => $totalPlans,
                'active_plans' => $activePlans,
                'active_subscriptions' => $activeSubs,
            ],
        ];
    }
}
