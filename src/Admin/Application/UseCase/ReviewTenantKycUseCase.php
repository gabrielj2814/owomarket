<?php

declare(strict_types=1);

namespace Src\Admin\Application\UseCase;

use Exception;
use Src\Tenant\Infrastructure\Eloquent\Models\TenantKycProfile;

/**
 * El administrador verifica o rechaza un expediente de identidad (subsistema 1).
 *
 * Rechazar EXIGE motivo. Sin el, el comerciante ve su cobro bloqueado y no sabe que corregir
 * --y acaba en soporte preguntando lo que la pantalla deberia haberle dicho--.
 */
final class ReviewTenantKycUseCase
{
    /**
     * @throws Exception 404 si no existe, 422 si se rechaza sin motivo.
     */
    public function execute(string $profileId, string $adminId, bool $aprobado, ?string $motivo = null): TenantKycProfile
    {
        $perfil = TenantKycProfile::find($profileId);

        if ($perfil === null) {
            throw new Exception('Expediente de verificación no encontrado.', 404);
        }

        if (! $aprobado && ($motivo === null || trim($motivo) === '')) {
            throw new Exception('Indica el motivo del rechazo: el comerciante necesita saber qué corregir.', 422);
        }

        $perfil->status = $aprobado ? 'verified' : 'rejected';
        $perfil->reviewed_at = now();
        $perfil->reviewed_by = $adminId;
        $perfil->rejection_reason = $aprobado ? null : trim((string) $motivo);
        $perfil->save();

        return $perfil;
    }
}
