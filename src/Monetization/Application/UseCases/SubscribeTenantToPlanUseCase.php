<?php

declare(strict_types=1);

namespace Src\Monetization\Application\UseCases;

use App\Models\SubscriptionPlan;
use App\Models\TenantSubscription;
use Exception;
use Illuminate\Support\Str;

final class SubscribeTenantToPlanUseCase
{
    /**
     * @param string $tenantId
     * @param string $planSlugOrId
     * @param string $billingCycle 'monthly' | 'yearly'
     * @return TenantSubscription
     */
    public function execute(string $tenantId, string $planSlugOrId, string $billingCycle = 'monthly'): TenantSubscription
    {
        $plan = SubscriptionPlan::where('id', $planSlugOrId)
            ->orWhere('slug', $planSlugOrId)
            ->first();

        if (! $plan || ! $plan->is_active) {
            throw new Exception('Plan de suscripción no encontrado o inactivo.', 404);
        }

        // Cancel existing active subscriptions for this tenant
        TenantSubscription::where('tenant_id', $tenantId)
            ->where('status', 'active')
            ->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
            ]);

        $startsAt = now();
        $endsAt = $billingCycle === 'yearly' ? now()->addYear() : now()->addMonth();

        return TenantSubscription::create([
            'id' => (string) Str::uuid(),
            'tenant_id' => $tenantId,
            'plan_id' => $plan->id,
            'billing_cycle' => in_array($billingCycle, ['monthly', 'yearly'], true) ? $billingCycle : 'monthly',
            'status' => 'active',
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'renews_at' => $endsAt,
        ]);
    }
}
