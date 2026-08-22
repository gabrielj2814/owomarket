<?php

declare(strict_types=1);

namespace Src\Tenant\Infrastructure\Http\Controller;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use Inertia\Response;
use Src\Monetization\Infrastructure\Eloquent\Models\SubscriptionPlan;
use Src\Monetization\Infrastructure\Eloquent\Models\TenantSubscription;
use Src\Tenant\Infrastructure\Eloquent\Models\Tenant;

final class ViewTenantOwnerBillingGETController extends Controller
{
    public function __invoke(Request $request, string $user_uuid): Response
    {
        $tenants = Tenant::whereHas('users', function ($q) use ($user_uuid) {
            $q->where('user_id', $user_uuid);
        })->get();

        if ($tenants->isEmpty()) {
            $tenants = Tenant::where('status', 'active')->limit(5)->get();
        }

        $subscriptions = [];
        if (Schema::hasTable('tenant_subscriptions')) {
            $subscriptions = TenantSubscription::with('plan')
                ->whereIn('tenant_id', $tenants->pluck('id')->toArray())
                ->get()
                ->toArray();
        }

        $availablePlans = [];
        if (Schema::hasTable('subscription_plans')) {
            $availablePlans = SubscriptionPlan::where('is_active', true)->orderBy('price_monthly', 'asc')->get()->toArray();
        }

        return Inertia::render('tenant/billing/TenantOwnerBillingPage', [
            'title' => 'Suscripciones y Facturación B2B - OwOMarket',
            'user_id' => $user_uuid,
            'tenants' => $tenants->map(fn ($t) => [
                'id' => $t->id,
                'name' => $t->name ?? ucfirst($t->slug),
                'slug' => $t->slug,
            ])->toArray(),
            'subscriptions' => $subscriptions,
            'available_plans' => $availablePlans,
        ]);
    }
}
