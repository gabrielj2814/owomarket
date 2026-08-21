<?php

declare(strict_types=1);

namespace Src\Admin\Application\UseCase;

use Src\Monetization\Infrastructure\Eloquent\Models\SubscriptionPlan;
use Exception;
use Illuminate\Support\Str;

final class SaveSubscriptionPlanUseCase
{
    /**
     * @param array{
     *     id?: string|null,
     *     name: string,
     *     slug?: string|null,
     *     description?: string|null,
     *     price_monthly: float|int,
     *     price_yearly?: float|int|null,
     *     commission_rate?: float|int,
     *     max_products?: int,
     *     features?: array<string>|null,
     *     is_active?: bool
     * } $data
     */
    public function execute(array $data): SubscriptionPlan
    {
        $id = $data['id'] ?? null;
        $name = trim($data['name']);
        $slug = ! empty($data['slug']) ? Str::slug($data['slug']) : Str::slug($name);

        if ($id) {
            $plan = SubscriptionPlan::find($id);
            if (! $plan) {
                throw new Exception("Plan de suscripción '{$id}' no encontrado.", 404);
            }
        } else {
            $plan = new SubscriptionPlan();
            $plan->id = (string) Str::uuid();
        }

        $plan->name = $name;
        $plan->slug = $slug;
        $plan->description = $data['description'] ?? $plan->description;
        $plan->price_monthly = (float) $data['price_monthly'];
        $plan->price_yearly = isset($data['price_yearly']) ? (float) $data['price_yearly'] : ($plan->price_monthly * 10);
        $plan->commission_rate = isset($data['commission_rate']) ? (float) $data['commission_rate'] : 5.0;
        $plan->max_products = isset($data['max_products']) ? (int) $data['max_products'] : 100;
        $plan->features = $data['features'] ?? $plan->features ?? [];
        $plan->is_active = isset($data['is_active']) ? (bool) $data['is_active'] : ($plan->is_active ?? true);

        $plan->save();

        return $plan;
    }
}
