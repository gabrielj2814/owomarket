<?php

declare(strict_types=1);

namespace Src\Admin\Application\UseCase;

use Src\Tenant\Infrastructure\Eloquent\Models\TenantKycProfile;

/**
 * Qué otras tiendas hay detrás de la misma identidad (subsistema 1).
 *
 * **Este caso de uso es la razón de ser del KYC.** La decisión de garantías dice que su valor
 * principal no es la denuncia sino que **la sanción sobreviva al cierre de la tienda**: sin
 * poder preguntar «¿esta cédula ya tenía una tienda?», un comerciante que quema su reputación
 * o deja una deuda se registra otra vez con otro nombre y empieza limpio, y todo el sistema de
 * reputación del subsistema 5 se vuelve teatro.
 *
 * La búsqueda va por hash y no por el dato cifrado porque **cifrado no se puede buscar**: el
 * cast `encrypted` usa un IV aleatorio y el mismo número da un valor distinto cada vez.
 *
 * Tener dos tiendas no es una falta. Esto no bloquea a nadie: informa a quien decide.
 */
final class FindTenantsByIdentityUseCase
{
    /**
     * @return array<int, array<string, mixed>> Expedientes que comparten cédula o RIF.
     */
    public function execute(?string $cedula = null, ?string $rif = null): array
    {
        $cedulaHash = TenantKycProfile::hashDe($cedula);
        $rifHash = TenantKycProfile::hashDe($rif);

        if ($cedulaHash === null && $rifHash === null) {
            return [];
        }

        return TenantKycProfile::with('tenant:id,name,slug,status')
            ->where(function ($q) use ($cedulaHash, $rifHash) {
                if ($cedulaHash !== null) {
                    $q->orWhere('cedula_hash', $cedulaHash);
                }
                if ($rifHash !== null) {
                    $q->orWhere('rif_hash', $rifHash);
                }
            })
            ->orderBy('created_at')
            ->get()
            ->map(fn (TenantKycProfile $p) => [
                'tenant_id' => $p->tenant_id,
                'tenant_name' => $p->tenant?->name,
                'tenant_status' => $p->tenant?->status,
                'legal_name' => $p->legal_name,
                'kyc_status' => $p->status,
                // Sin devolver cedula ni RIF: quien consulta ya sabe por cual busco, y
                // repetirlos en la respuesta solo multiplicaria los sitios donde pueden
                // acabar --un log, una captura, un ticket de soporte--.
                'created_at' => $p->created_at?->toIso8601String(),
            ])
            ->all();
    }
}
