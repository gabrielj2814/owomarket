<?php

declare(strict_types=1);

namespace Src\Tenant\Infrastructure\Http\Controller;

use Illuminate\Http\JsonResponse;
use Src\Shared\Helper\ApiResponse;
use Src\Tenant\Application\Service\TenantOwnershipVerifier;
use Src\Tenant\Infrastructure\Eloquent\Models\TenantKycProfile;

/**
 * El estado de la verificacion, para que la pantalla sepa que pintar (subsistema 1).
 *
 * **No devuelve la cedula ni el RIF.** Ni siquiera a su propio dueño: ya los tiene, y
 * devolverlos solo multiplicaria los sitios por donde pueden escaparse --una cache del
 * navegador, un log de red, una captura de pantalla en un ticket--. El modelo los oculta al
 * serializar, y aqui se construye la respuesta a mano para que eso no dependa de que nadie se
 * acuerde.
 */
final class GetTenantKycStatusGETController
{
    public function __construct(
        private readonly TenantOwnershipVerifier $ownership
    ) {}

    public function __invoke(string $tenantId): JsonResponse
    {
        $userId = (string) (auth()->id() ?? '');

        if ($userId === '') {
            return ApiResponse::error('Debes iniciar sesión.', 401);
        }

        $this->ownership->ensureOwns($userId, $tenantId);

        $perfil = TenantKycProfile::where('tenant_id', $tenantId)->first();

        return ApiResponse::success([
            'status' => $perfil?->status ?? 'missing',
            'legal_name' => $perfil?->legal_name,
            'phone' => $perfil?->phone,
            'address' => $perfil?->address,
            'has_document' => $perfil?->document_path !== null,
            'rejection_reason' => $perfil?->rejection_reason,
            'reviewed_at' => $perfil?->reviewed_at?->toIso8601String(),
        ], 'Estado de verificación');
    }
}
