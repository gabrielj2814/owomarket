<?php

declare(strict_types=1);

namespace Src\Admin\Application\UseCase;

use Src\Monetization\Infrastructure\Eloquent\Models\SubscriptionPlan;
use Exception;

final class DeleteSubscriptionPlanUseCase
{
    public function execute(string $id): bool
    {
        $plan = SubscriptionPlan::find($id);

        if (! $plan) {
            throw new Exception("Plan de suscripción '{$id}' no encontrado.", 404);
        }

        return (bool) $plan->delete();
    }
}
