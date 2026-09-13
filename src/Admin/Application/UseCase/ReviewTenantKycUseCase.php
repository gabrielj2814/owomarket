<?php

declare(strict_types=1);

namespace Src\Admin\Application\UseCase;

use Exception;
use Src\Notification\Application\Contracts\NotificationDispatcher;
use Src\Tenant\Infrastructure\Eloquent\Models\TenantKycProfile;

/**
 * El administrador verifica o rechaza un expediente de identidad (subsistema 1).
 *
 * Rechazar EXIGE motivo. Sin el, el comerciante ve su cobro bloqueado y no sabe que corregir
 * --y acaba en soporte preguntando lo que la pantalla deberia haberle dicho--.
 */
final class ReviewTenantKycUseCase
{
    public function __construct(
        private readonly NotificationDispatcher $avisos
    ) {}

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

        /*
         * Sin KYC verificado una tienda no puede cobrar, asi que este aviso es la diferencia
         * entre esperar sabiendo y esperar sin saber. Y si se rechazo, el motivo viaja con el:
         * un rechazo sin motivo deja al comerciante adivinando que corregir.
         */
        $this->avisos->kycReviewed($perfil->id);

        return $perfil;
    }
}
