<?php

declare(strict_types=1);

namespace Src\Tenant\Application\UseCase;

use Exception;
use Illuminate\Support\Str;
use Src\Tenant\Application\Service\TenantOwnershipVerifier;
use Src\Tenant\Infrastructure\Eloquent\Models\TenantKycProfile;

/**
 * El comerciante envia --o corrige-- su expediente de identidad (subsistema 1).
 *
 * Se puede reenviar mientras no este verificado: un expediente rechazado tiene que poder
 * corregirse, o el comerciante se queda sin via para cobrar y sin forma de arreglarlo. Lo que
 * no se puede es tocar uno ya verificado, porque cambiar la cedula despues de que un
 * administrador la aprobo vaciaria de sentido la revision.
 */
final class SubmitTenantKycUseCase
{
    public function __construct(
        private readonly TenantOwnershipVerifier $ownership
    ) {}

    /**
     * @param array{
     *     legal_name: string,
     *     cedula: string,
     *     nationality?: string,
     *     rif?: string|null,
     *     phone: string,
     *     address: string,
     *     document_path?: string|null
     * } $data
     *
     * @throws Exception 403 si no es su tienda, 409 si ya esta verificado.
     */
    public function execute(string $userId, string $tenantId, array $data): TenantKycProfile
    {
        $this->ownership->ensureOwns($userId, $tenantId);

        $perfil = TenantKycProfile::where('tenant_id', $tenantId)->first();

        if ($perfil?->isVerified()) {
            throw new Exception(
                'Tu identidad ya está verificada. Si necesitas cambiar algún dato, escríbenos a soporte.',
                409
            );
        }

        $atributos = [
            'legal_name' => $data['legal_name'],
            'cedula' => $data['cedula'],
            'nationality' => strtoupper($data['nationality'] ?? 'V'),
            'rif' => $data['rif'] ?? null,
            'phone' => $data['phone'],
            'address' => $data['address'],
            'document_path' => $data['document_path'] ?? null,
            // Reenviar devuelve el expediente a la cola de revision y limpia el motivo del
            // rechazo anterior: dejarlo puesto haria que el comerciante siguiera leyendo en
            // pantalla un reproche que ya corrigio.
            'status' => 'pending',
            'reviewed_at' => null,
            'reviewed_by' => null,
            'rejection_reason' => null,
        ];

        if ($perfil === null) {
            return TenantKycProfile::create(array_merge($atributos, [
                'id' => (string) Str::uuid(),
                'tenant_id' => $tenantId,
                'user_id' => $userId,
            ]));
        }

        $perfil->fill($atributos)->save();

        return $perfil;
    }
}
