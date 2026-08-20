<?php

declare(strict_types=1);

namespace Src\Tenant\Application\UseCase;

use App\Models\TenantOwnerSsoToken;
use Exception;
use Illuminate\Support\Str;
use Src\Tenant\Infrastructure\Eloquent\Models\Tenant;
use Src\Tenant\Infrastructure\Eloquent\Models\User;

final class GenerateTenantOwnerSsoTokenUseCase
{
    /**
     * @return array{token: string, redirect_url: string, expires_at: string}
     */
    public function execute(string $userId, string $tenantId): array
    {
        // 1. Verificar que el usuario exista
        $user = User::find($userId);
        if (! $user) {
            throw new Exception('Usuario no encontrado', 404);
        }

        // 2. Verificar que el tenant exista
        $tenant = Tenant::with('domains')->find($tenantId);
        if (! $tenant) {
            throw new Exception('Tienda no encontrada', 404);
        }

        // 3. Obtener dominio del inquilino
        $domainModel = $tenant->domains->first();
        $tenantDomain = $domainModel ? $domainModel->domain : "{$tenant->slug}.localhost";

        // 4. Generar token único de 64 caracteres
        $tokenString = bin2hex(random_bytes(32));
        $expiresAt = now()->addMinutes(15);

        TenantOwnerSsoToken::create([
            'id' => (string) Str::uuid(),
            'user_id' => $userId,
            'tenant_id' => $tenantId,
            'token' => $tokenString,
            'target_domain' => $tenantDomain,
            'expires_at' => $expiresAt,
            'created_at' => now(),
        ]);

        $scheme = request()->getScheme() ?: 'http';
        $redirectUrl = "{$scheme}://{$tenantDomain}/auth/sso-consume?token={$tokenString}";

        return [
            'token' => $tokenString,
            'redirect_url' => $redirectUrl,
            'expires_at' => $expiresAt->toIso8601String(),
        ];
    }
}
