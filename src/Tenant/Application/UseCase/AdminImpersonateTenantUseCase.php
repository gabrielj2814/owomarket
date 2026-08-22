<?php

declare(strict_types=1);

namespace Src\Tenant\Application\UseCase;

use Exception;
use Illuminate\Support\Str;
use Src\Admin\Infrastructure\Eloquent\Models\CentralAuditLog;
use Src\Tenant\Infrastructure\Eloquent\Models\Tenant;
use Src\Tenant\Infrastructure\Eloquent\Models\TenantOwnerSsoToken;
use Src\Tenant\Infrastructure\Eloquent\Models\User;

final class AdminImpersonateTenantUseCase
{
    /**
     * @return array{
     *     sso_url: string,
     *     token: string,
     *     expires_at: string,
     *     domain: string
     * }
     */
    public function execute(string $tenantId, string $adminUserId): array
    {
        $tenant = Tenant::with('domains')->find($tenantId);

        if (! $tenant) {
            throw new Exception("Tienda inquilina '{$tenantId}' no encontrada.", 404);
        }

        $adminUser = User::find($adminUserId);
        if (! $adminUser) {
            throw new Exception('Usuario administrador no encontrado.', 404);
        }

        // Obtener el dominio del inquilino
        $domainModel = $tenant->domains->first();
        $domain = $domainModel ? $domainModel->domain : "{$tenant->slug}.owomarket.local";

        // Generar token SSO efímero (válido por 10 minutos)
        $token = Str::random(64);
        $expiresAt = now()->addMinutes(10);

        TenantOwnerSsoToken::create([
            'id' => (string) Str::uuid(),
            'user_id' => $adminUser->id,
            'tenant_id' => $tenant->id,
            'token' => $token,
            'expires_at' => $expiresAt,
        ]);

        $scheme = config('app.env') === 'production' ? 'https' : 'http';
        // Hallazgo A9: apuntaba a `/auth/sso`, ruta que NO EXISTE. La real es
        // `/auth/sso-consume` (ver `src/Tenant/.../Routes/web.php`), asi que el boton de
        // «acceso directo» del expediente 360 daba 404 y la funcion no servia.
        $ssoUrl = "{$scheme}://{$domain}/auth/sso-consume?token={$token}";

        // Hallazgo A9: entrar como otro es de las cosas mas sensibles que puede hacer un
        // superadmin y no dejaba ningun rastro, a diferencia de `AssignUserRolesUseCase`.
        CentralAuditLog::create([
            'id' => (string) Str::uuid(),
            'user_id' => $adminUser->id,
            'user_name' => $adminUser->name,
            'user_email' => $adminUser->email,
            'action' => 'tenant.impersonate',
            'entity_type' => Tenant::class,
            'entity_id' => $tenant->id,
            'description' => "Acceso directo a la tienda «{$tenant->name}» ({$domain}).",
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        return [
            'sso_url' => $ssoUrl,
            'token' => $token,
            'expires_at' => $expiresAt->toIso8601String(),
            'domain' => $domain,
        ];
    }
}
