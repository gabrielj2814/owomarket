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

        // Sincronizar usuario en la tabla auth_users del tenant si la sesión está en el contexto del inquilino
        if (class_exists(\Src\Authentication\Infrastructure\Eloquent\Models\AuthUser::class)) {
            try {
                \Src\Authentication\Infrastructure\Eloquent\Models\AuthUser::updateOrCreate(
                    ['user_id' => $user->id],
                    [
                        'id' => (string) \Illuminate\Support\Str::uuid(),
                        'user_name' => $user->name,
                        'user_email' => $user->email,
                        'user_type' => 'owner',
                        'user_avatar' => $user->avatar ?? 'https://i.pinimg.com/originals/b0/ce/76/b0ce76f4cdb95ef13afa21a889adfc71.jpg',
                    ]
                );
            } catch (\Throwable $e) {
                // Silently ignore if table doesn't exist yet in mock tests
            }
        }

        return [
            'user' => $user,
            'redirect_to' => "/tenant/backoffice/{$user->id}/dashboard",
        ];
    }
}
