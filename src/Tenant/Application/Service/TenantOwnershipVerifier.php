<?php

declare(strict_types=1);

namespace Src\Tenant\Application\Service;

use Exception;
use Src\Tenant\Infrastructure\Eloquent\Models\Tenant;

/**
 * Resuelve y verifica qué tiendas pertenecen a un usuario.
 *
 * La relación de propiedad vive en la tabla pivote `tenant_users`
 * (ver Tenant::users() y User::tenants()).
 *
 * Este servicio es la ÚNICA fuente de verdad para "¿este usuario manda en esta tienda?".
 * Los casos de uso del panel del propietario deben usarlo en lugar de confiar en un
 * `tenant_id` que llega por la petición HTTP.
 *
 * NOTA: no concede privilegios especiales al super administrador. Un super admin
 * sin tiendas asociadas obtiene una lista vacía, no las tiendas de otro comerciante.
 * Para operar sobre una tienda ajena, el super admin dispone de su propio backoffice
 * y del flujo de impersonación auditado.
 */
final class TenantOwnershipVerifier
{
    /**
     * Identificadores de las tiendas del usuario. Lista vacía si no tiene ninguna.
     *
     * @return array<int, string>
     */
    public function tenantIdsOf(string $userId): array
    {
        if (trim($userId) === '') {
            return [];
        }

        return Tenant::whereHas('users', fn ($q) => $q->where('user_id', $userId))
            ->pluck('id')
            ->map(fn ($id) => (string) $id)
            ->all();
    }

    /**
     * Tiendas del usuario como colección de modelos. Colección vacía si no tiene ninguna.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, Tenant>
     */
    public function tenantsOf(string $userId)
    {
        if (trim($userId) === '') {
            return Tenant::whereRaw('1 = 0')->get();
        }

        return Tenant::whereHas('users', fn ($q) => $q->where('user_id', $userId))->get();
    }

    public function owns(string $userId, string $tenantId): bool
    {
        if (trim($userId) === '' || trim($tenantId) === '') {
            return false;
        }

        return Tenant::where('id', $tenantId)
            ->whereHas('users', fn ($q) => $q->where('user_id', $userId))
            ->exists();
    }

    /**
     * Devuelve la tienda si el usuario es su propietario; si no, lanza excepción.
     *
     * @throws Exception 404 si la tienda no existe, 403 si no le pertenece
     */
    public function ensureOwns(string $userId, string $tenantId): Tenant
    {
        $tenant = Tenant::find($tenantId);

        if (! $tenant) {
            throw new Exception('La tienda especificada no existe.', 404);
        }

        if (! $this->owns($userId, $tenantId)) {
            throw new Exception('No tienes acceso a esta tienda.', 403);
        }

        return $tenant;
    }
}
