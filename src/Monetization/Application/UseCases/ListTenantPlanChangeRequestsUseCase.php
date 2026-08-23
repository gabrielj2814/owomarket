<?php

declare(strict_types=1);

namespace Src\Monetization\Application\UseCases;

use Src\Monetization\Infrastructure\Eloquent\Models\TenantPlanChangeRequest;

/**
 * Listado de solicitudes de cambio de plan para el panel de administracion (hallazgo T3).
 */
final class ListTenantPlanChangeRequestsUseCase
{
    /**
     * @return array<string, mixed>
     */
    public function execute(?string $status = null): array
    {
        $consulta = TenantPlanChangeRequest::with(['tenant', 'requestedPlan', 'currentPlan'])
            ->orderByDesc('created_at');

        if ($status !== null && $status !== '' && $status !== 'all') {
            $consulta->where('status', $status);
        }

        $solicitudes = $consulta->get()->map(fn ($s) => [
            'id' => $s->id,
            'tenant_id' => $s->tenant_id,
            'tenant_name' => $s->tenant?->name ?? $s->tenant_id,
            'current_plan' => $s->currentPlan?->name,
            'requested_plan' => $s->requestedPlan?->name,
            'current_commission_rate' => $s->currentPlan ? (float) $s->currentPlan->commission_rate : null,
            'requested_commission_rate' => $s->requestedPlan ? (float) $s->requestedPlan->commission_rate : null,
            'billing_cycle' => $s->billing_cycle,
            'status' => $s->status,
            'notes' => $s->notes,
            'rejection_reason' => $s->rejection_reason,
            'created_at' => $s->created_at?->toIso8601String(),
            'resolved_at' => $s->resolved_at?->toIso8601String(),
        ])->all();

        return [
            'requests' => $solicitudes,
            'metrics' => [
                'pending_count' => TenantPlanChangeRequest::where('status', 'pending')->count(),
                'approved_count' => TenantPlanChangeRequest::where('status', 'approved')->count(),
                'rejected_count' => TenantPlanChangeRequest::where('status', 'rejected')->count(),
            ],
        ];
    }
}
