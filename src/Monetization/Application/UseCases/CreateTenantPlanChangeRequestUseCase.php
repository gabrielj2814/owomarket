<?php

declare(strict_types=1);

namespace Src\Monetization\Application\UseCases;

use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Src\Monetization\Infrastructure\Eloquent\Models\SubscriptionPlan;
use Src\Monetization\Infrastructure\Eloquent\Models\TenantPlanChangeRequest;
use Src\Monetization\Infrastructure\Eloquent\Models\TenantSubscription;
use Src\Tenant\Application\Service\TenantOwnershipVerifier;

/**
 * El comerciante pide cambiar de plan (hallazgo T3).
 *
 * Antes esto no existia: el boton mostraba un `alert()` diciendo que la solicitud quedaba
 * registrada y no se registraba nada.
 */
final class CreateTenantPlanChangeRequestUseCase
{
    public function __construct(
        private readonly TenantOwnershipVerifier $ownership
    ) {}

    /**
     * @param  array{tenant_id: string, plan_id: string, billing_cycle?: string, notes?: string|null}  $data
     */
    public function execute(string $userId, array $data): TenantPlanChangeRequest
    {
        // 404 si la tienda no existe, 403 si es de otro comerciante. Mismo verificador que
        // usan los retiros: la identidad sale de la sesion, nunca del cuerpo de la peticion.
        $this->ownership->ensureOwns($userId, $data['tenant_id']);

        $plan = SubscriptionPlan::find($data['plan_id']);

        if ($plan === null || ! $plan->is_active) {
            throw new Exception('El plan solicitado no existe o no está disponible.', 422);
        }

        return DB::transaction(function () use ($userId, $data, $plan) {
            $suscripcionActual = TenantSubscription::where('tenant_id', $data['tenant_id'])
                ->where('status', 'active')
                ->lockForUpdate()
                ->first();

            if ($suscripcionActual !== null && (string) $suscripcionActual->plan_id === (string) $plan->id) {
                throw new Exception('Esa tienda ya está en el plan '.$plan->name.'.', 422);
            }

            // Una pendiente por tienda. Sin esto, pulsar el boton tres veces genera tres
            // solicitudes y el administrador no sabe cual resolver.
            $yaPendiente = TenantPlanChangeRequest::where('tenant_id', $data['tenant_id'])
                ->where('status', 'pending')
                ->lockForUpdate()
                ->exists();

            if ($yaPendiente) {
                throw new Exception('Ya tienes una solicitud de cambio de plan pendiente de revisión.', 409);
            }

            return TenantPlanChangeRequest::create([
                'id' => (string) Str::uuid(),
                'tenant_id' => $data['tenant_id'],
                'current_plan_id' => $suscripcionActual?->plan_id,
                'requested_plan_id' => $plan->id,
                'billing_cycle' => $data['billing_cycle'] ?? 'monthly',
                'status' => 'pending',
                'requested_by_user_id' => $userId,
                'notes' => $data['notes'] ?? null,
            ]);
        });
    }
}
