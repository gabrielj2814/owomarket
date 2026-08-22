<?php

declare(strict_types=1);

namespace Src\Monetization\Application\UseCases;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;
use Src\Monetization\Infrastructure\Eloquent\Models\SubscriptionPlan;

final class ListSubscriptionPlansUseCase
{
    /**
     * @return Collection<int, SubscriptionPlan>
     */
    public function execute(): Collection
    {
        $plans = SubscriptionPlan::where('is_active', true)->get();

        if ($plans->isEmpty()) {
            $this->seedDefaultPlans();
            $plans = SubscriptionPlan::where('is_active', true)->get();
        }

        return $plans;
    }

    private function seedDefaultPlans(): void
    {
        SubscriptionPlan::create([
            'id' => (string) Str::uuid(),
            'name' => 'Plan Gratuito / Básico',
            'slug' => 'free',
            'description' => 'Ideal para comenzar tu tienda sin costo fijo mensual.',
            'price_monthly' => 0.00,
            'price_yearly' => 0.00,
            'commission_rate' => 8.00,
            'features' => ['Catalogo basico', 'Pagos manuales y Pago Movil', 'Soporte estandar'],
            'max_products' => 50,
            'is_active' => true,
        ]);

        SubscriptionPlan::create([
            'id' => (string) Str::uuid(),
            'name' => 'Plan Emprendedor Pro',
            'slug' => 'pro',
            'description' => 'Comisiones reducidas y acceso a cupones, facturacion avanzada y Binance Pay.',
            'price_monthly' => 19.99,
            'price_yearly' => 199.99,
            'commission_rate' => 3.50,
            'features' => ['Productos ilimitados', 'Comision reducida al 3.5%', 'Cupones y Descuentos', 'Facturacion PDF automatica', 'Binance Pay'],
            'max_products' => 1000,
            'is_active' => true,
        ]);

        SubscriptionPlan::create([
            'id' => (string) Str::uuid(),
            'name' => 'Plan Enterprise / Ilimitado',
            'slug' => 'enterprise',
            'description' => 'La comision mas baja del mercado (1.0%) con soporte prioritario 24/7 y multi-sucursal.',
            'price_monthly' => 49.99,
            'price_yearly' => 499.99,
            'commission_rate' => 1.00,
            'features' => ['Todo lo de Pro', 'Comision minima de 1.0%', 'Multi-sucursal', 'API Webhooks dedicados', 'Soporte VIP'],
            'max_products' => null,
            'is_active' => true,
        ]);
    }
}
