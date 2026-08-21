<?php

declare(strict_types=1);

namespace Src\Tenant\Application\UseCase;

use Src\Tenant\Infrastructure\Eloquent\Models\TenantOwnerSsoToken;
use Exception;
use Illuminate\Support\Str;
use Src\Tenant\Infrastructure\Eloquent\Models\Tenant;
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
            throw new Exception("Usuario administrador no encontrado.", 404);
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
        $ssoUrl = "{$scheme}://{$domain}/auth/sso?token={$token}";

        return [
            'sso_url' => $ssoUrl,
            'token' => $token,
            'expires_at' => $expiresAt->toIso8601String(),
            'domain' => $domain,
        ];
    }
}
