<?php

declare(strict_types=1);

namespace Src\Monetization\Application\UseCases;

use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Src\Monetization\Infrastructure\Eloquent\Models\SubscriptionPlan;
use Src\Monetization\Infrastructure\Eloquent\Models\TenantPlanChangeRequest;
use Src\Monetization\Infrastructure\Eloquent\Models\TenantSubscription;

/**
 * El administrador aprueba el cambio de plan (hallazgo T3).
 *
 * **Revalida que el plan siga activo, y no da por buena la comprobacion de la solicitud.**
 * Esa es la leccion de T1: alli el saldo se comprobaba al PEDIR el retiro y nunca mas, asi
 * que cualquier cambio entre la solicitud y la aprobacion acababa en un pago sin respaldo.
 * Aqui el equivalente seria mover una tienda a un plan que se desactivo mientras la
 * solicitud esperaba — y con el plan viaja la `commission_rate`.
 */
final class ApproveTenantPlanChangeRequestUseCase
{
    public function execute(string $requestId, string $adminUserId): TenantPlanChangeRequest
    {
        return DB::transaction(function () use ($requestId, $adminUserId) {
            $solicitud = TenantPlanChangeRequest::lockForUpdate()->find($requestId);

            if ($solicitud === null) {
                throw new Exception('Solicitud de cambio de plan no encontrada.', 404);
            }

            if ($solicitud->status !== 'pending') {
                throw new Exception("No se puede aprobar una solicitud en estado '{$solicitud->status}'.", 400);
            }

            $plan = SubscriptionPlan::find($solicitud->requested_plan_id);

            if ($plan === null || ! $plan->is_active) {
                throw new Exception(
                    'El plan solicitado ya no está disponible. Se desactivó después de la solicitud.',
                    422
                );
            }

            // La suscripcion vigente se cierra; no se edita. Asi queda el historial de en
            // que plan estuvo la tienda y desde cuando, que es lo que hace auditable una
            // comision pasada.
            $vigente = TenantSubscription::where('tenant_id', $solicitud->tenant_id)
                ->where('status', 'active')
                ->lockForUpdate()
                ->first();

            if ($vigente !== null) {
                $vigente->update([
                    'status' => 'cancelled',
                    'cancelled_at' => now(),
                    'ends_at' => now(),
                ]);
            }

            TenantSubscription::create([
                'id' => (string) Str::uuid(),
                'tenant_id' => $solicitud->tenant_id,
                'plan_id' => $plan->id,
                'billing_cycle' => $solicitud->billing_cycle,
                'status' => 'active',
                'starts_at' => now(),
                'renews_at' => $solicitud->billing_cycle === 'yearly' ? now()->addYear() : now()->addMonth(),
            ]);

            $solicitud->update([
                'status' => 'approved',
                'resolved_by_user_id' => $adminUserId,
                'resolved_at' => now(),
            ]);

            return $solicitud->fresh();
        });
    }
}
