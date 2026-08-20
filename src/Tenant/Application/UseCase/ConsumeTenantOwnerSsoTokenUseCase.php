<?php

declare(strict_types=1);

namespace Src\Tenant\Application\UseCase;

use App\Models\TenantOwnerSsoToken;
use Exception;
use Illuminate\Support\Facades\Auth;
use Src\Tenant\Infrastructure\Eloquent\Models\User;

final class ConsumeTenantOwnerSsoTokenUseCase
{
    /**
     * @return array{user: User, redirect_to: string}
     */
    public function execute(string $token): array
    {
        $ssoToken = TenantOwnerSsoToken::where('token', $token)
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->first();

        if (! $ssoToken) {
            throw new Exception('El token SSO es inválido o ha expirado.', 401);
        }

        // Marcar token como consumido
        $ssoToken->update(['used_at' => now()]);

        // Buscar usuario en base de datos central/tenant
        $user = User::find($ssoToken->user_id);
        if (! $user) {
            throw new Exception('Usuario asociado al token SSO no encontrado.', 404);
        }

        return [
            'user' => $user,
            'redirect_to' => '/dashboard',
        ];
    }
}
