<?php

declare(strict_types=1);

namespace Src\Monetization\Application\UseCases;

use Exception;
use Src\Monetization\Infrastructure\Eloquent\Models\TenantPlanChangeRequest;

/**
 * El administrador rechaza el cambio de plan (hallazgo T3).
 *
 * El motivo es obligatorio, igual que en el rechazo de retiros: una solicitud que vuelve
 * rechazada sin explicacion deja al comerciante sin saber que corregir.
 */
final class RejectTenantPlanChangeRequestUseCase
{
    public function execute(string $requestId, string $adminUserId, string $motivo): TenantPlanChangeRequest
    {
        $solicitud = TenantPlanChangeRequest::find($requestId);

        if ($solicitud === null) {
            throw new Exception('Solicitud de cambio de plan no encontrada.', 404);
        }

        if ($solicitud->status !== 'pending') {
            throw new Exception("No se puede rechazar una solicitud en estado '{$solicitud->status}'.", 400);
        }

        if (trim($motivo) === '') {
            throw new Exception('El motivo del rechazo es obligatorio.', 422);
        }

        $solicitud->update([
            'status' => 'rejected',
            'resolved_by_user_id' => $adminUserId,
            'resolved_at' => now(),
            'rejection_reason' => trim($motivo),
        ]);

        return $solicitud->fresh();
    }
}
