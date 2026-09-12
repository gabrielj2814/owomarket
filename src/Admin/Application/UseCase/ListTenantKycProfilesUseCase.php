<?php

declare(strict_types=1);

namespace Src\Admin\Application\UseCase;

use Src\Tenant\Infrastructure\Eloquent\Models\TenantKycProfile;

/**
 * Los expedientes de identidad que esperan revisión (subsistema 1).
 *
 * **Lo pendiente primero y lo más viejo arriba.** No es una preferencia estética: cada
 * expediente en `pending` es una tienda que no puede cobrar, así que el de arriba es el que
 * lleva más tiempo con el dinero de alguien retenido. Cualquier otro orden esconde justo lo
 * que hay que atender.
 *
 * El orden se resuelve en SQL y no en la pantalla porque el índice `kyc_pendientes_index`
 * —`(status, created_at)`— existe exactamente para esto.
 *
 * ## Lo que NO devuelve
 *
 * Ni `cedula` ni `rif`, ni siquiera sus hashes. El modelo los oculta al serializar, pero aquí
 * se construye el array a mano campo por campo en vez de devolver el modelo: así el día que
 * alguien quite una línea de `$hidden`, esta respuesta sigue sin llevarlos. Que el número de
 * documento no viaje es una decisión del subsistema, no un efecto secundario de un `$hidden`.
 */
final class ListTenantKycProfilesUseCase
{
    private const POR_PAGINA = 15;

    /**
     * @param  array{status?: string|null, search?: string|null, page?: int, per_page?: int}  $filtros
     * @return array{profiles: array<int, array<string, mixed>>, pagination: array<string, int>, metrics: array<string, int>}
     */
    public function execute(array $filtros = []): array
    {
        $consulta = TenantKycProfile::with('tenant:id,name,slug,status');

        $estado = $filtros['status'] ?? null;
        if ($estado !== null && $estado !== '' && $estado !== 'all') {
            $consulta->where('status', $estado);
        }

        $busqueda = trim((string) ($filtros['search'] ?? ''));
        if ($busqueda !== '') {
            // Por nombre legal, teléfono o tienda. NO por cédula: está cifrada y buscarla
            // exigiría el hash, que es otra pregunta --«¿quién más es esta persona?»-- y tiene
            // su propio endpoint.
            $consulta->where(function ($q) use ($busqueda) {
                $q->where('legal_name', 'like', "%{$busqueda}%")
                    ->orWhere('phone', 'like', "%{$busqueda}%")
                    ->orWhereHas('tenant', fn ($t) => $t->where('name', 'like', "%{$busqueda}%"));
            });
        }

        $porPagina = max(1, (int) ($filtros['per_page'] ?? self::POR_PAGINA));
        $pagina = max(1, (int) ($filtros['page'] ?? 1));

        $paginador = $consulta
            ->orderByRaw("CASE WHEN status = 'pending' THEN 0 ELSE 1 END")
            ->orderBy('created_at')
            ->paginate(perPage: $porPagina, page: $pagina);

        return [
            'profiles' => collect($paginador->items())
                ->map(fn (TenantKycProfile $p) => $this->comoFila($p))
                ->all(),
            'pagination' => [
                'total' => $paginador->total(),
                'current_page' => $paginador->currentPage(),
                'per_page' => $paginador->perPage(),
                'last_page' => $paginador->lastPage(),
            ],
            'metrics' => $this->metricas(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function comoFila(TenantKycProfile $perfil): array
    {
        return [
            'id' => $perfil->id,
            'tenant_id' => $perfil->tenant_id,
            'tenant_name' => $perfil->tenant?->name,
            'tenant_slug' => $perfil->tenant?->slug,
            'tenant_status' => $perfil->tenant?->status,
            'legal_name' => $perfil->legal_name,
            'nationality' => $perfil->nationality,
            'phone' => $perfil->phone,
            'address' => $perfil->address,
            'has_document' => $perfil->document_path !== null && $perfil->document_path !== '',
            'status' => $perfil->status,
            'rejection_reason' => $perfil->rejection_reason,
            'reviewed_at' => $perfil->reviewed_at?->toIso8601String(),
            'reviewed_by' => $perfil->reviewed_by,
            'created_at' => $perfil->created_at?->toIso8601String(),
        ];
    }

    /**
     * Cuántos hay en cada estado, para que la pantalla pueda decir «3 tiendas no pueden
     * cobrar» en vez de obligar a contar filas.
     *
     * @return array<string, int>
     */
    private function metricas(): array
    {
        $porEstado = TenantKycProfile::query()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return [
            'pending_count' => (int) ($porEstado['pending'] ?? 0),
            'verified_count' => (int) ($porEstado['verified'] ?? 0),
            'rejected_count' => (int) ($porEstado['rejected'] ?? 0),
        ];
    }
}
