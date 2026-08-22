<?php

declare(strict_types=1);

namespace Src\Marketplace\Infrastructure\Http\Controller;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use Inertia\Response;
use Src\Monetization\Infrastructure\Eloquent\Models\SubscriptionPlan;
use Src\Product\Infrastructure\Eloquent\Models\CentralProduct;
use Src\Tenant\Infrastructure\Eloquent\Models\Tenant;

final class ViewMerchantLandingGETController
{
    public function __invoke(Request $request): Response
    {
        $domain = $request->getHost();

        // 1. Tiendas activas para showcase
        $featuredStores = [];
        if (Schema::hasTable('tenants')) {
            $featuredStores = Tenant::where('status', 'active')
                ->where('request', 'approved')
                ->with(['domains'])
                ->limit(8)
                ->get()
                ->map(function ($tenant) {
                    $domainModel = $tenant->domains->first();
                    $tenantDomain = $domainModel ? $domainModel->domain : "{$tenant->slug}.localhost";

                    $productsCount = 0;
                    if (Schema::hasTable('central_products')) {
                        $productsCount = CentralProduct::where('tenant_id', $tenant->id)->where('is_visible', true)->count();
                    }

                    return [
                        'id' => $tenant->id,
                        'name' => $tenant->name ?? ucfirst($tenant->slug),
                        'slug' => $tenant->slug,
                        'domain' => $tenantDomain,
                        'logo' => $tenant->data['logo_url'] ?? null,
                        'products_count' => $productsCount,
                    ];
                })
                ->toArray();
        }

        // 2. Planes de monetización
        $plans = [];
        if (Schema::hasTable('subscription_plans')) {
            $plans = SubscriptionPlan::where('is_active', true)
                ->orderBy('price_monthly', 'asc')
                ->get()
                ->map(function ($plan) {
                    return [
                        'id' => $plan->id,
                        'name' => $plan->name,
                        'slug' => $plan->slug,
                        'price' => (float) $plan->price_monthly,
                        'billing_interval' => 'monthly',
                        'commission_rate' => (float) $plan->commission_rate,
                        'max_products' => $plan->max_products,
                        'features' => $plan->features ?? [],
                    ];
                })
                ->toArray();
        }

        if (empty($plans)) {
            $plans = [
                [
                    'id' => 'free',
                    'name' => 'Plan Inicial / Gratuito',
                    'slug' => 'free',
                    'price' => 0.00,
                    'billing_interval' => 'monthly',
                    'commission_rate' => 5.0,
                    'max_products' => 50,
                    'features' => [
                        'Tienda online con subdominio propio',
                        'Publicación en el Marketplace Central',
                        'Pagos en Bolívares (Pago Móvil BCV) y USDT (Binance Pay)',
                        'Facturación digital en PDF',
                        'Hasta 50 productos en catálogo',
                    ],
                ],
                [
                    'id' => 'pro',
                    'name' => 'Plan Profesional',
                    'slug' => 'pro',
                    'price' => 19.99,
                    'billing_interval' => 'monthly',
                    'commission_rate' => 3.0,
                    'max_products' => 500,
                    'features' => [
                        'Todo lo del Plan Inicial',
                        'Comisión reducida al 3.0%',
                        'Insignia de Tienda Verificada',
                        'Gestión avanzada de cupones y promociones',
                        'Soporte prioritario por WhatsApp',
                    ],
                ],
                [
                    'id' => 'enterprise',
                    'name' => 'Plan Enterprise',
                    'slug' => 'enterprise',
                    'price' => 49.99,
                    'billing_interval' => 'monthly',
                    'commission_rate' => 1.5,
                    'max_products' => 5000,
                    'features' => [
                        'Todo lo del Plan Profesional',
                        'Comisión mínima del 1.5%',
                        'Dominio personalizado (mitienda.com)',
                        'Posicionamiento VIP en Portada Central',
                        'Gerente de cuenta dedicado',
                    ],
                ],
            ];
        }

        return Inertia::render('marketplace/landing/MerchantLandingPage', [
            'domain' => $domain,
            'featured_stores' => $featuredStores,
            'plans' => $plans,
            'total_stores_count' => Tenant::where('status', 'active')->count(),
            'total_products_count' => CentralProduct::where('is_visible', true)->count(),
        ]);
    }
}
