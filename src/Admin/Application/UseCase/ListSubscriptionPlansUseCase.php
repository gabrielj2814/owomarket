<?php

declare(strict_types=1);

namespace Src\Admin\Application\UseCase;

use App\Models\SubscriptionPlan;
use App\Models\TenantSubscription;
use Illuminate\Database\Eloquent\Collection;

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
