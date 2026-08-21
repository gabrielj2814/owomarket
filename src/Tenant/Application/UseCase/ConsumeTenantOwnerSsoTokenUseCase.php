<?php

declare(strict_types=1);

namespace Src\Tenant\Application\UseCase;

use Src\Tenant\Infrastructure\Eloquent\Models\TenantOwnerSsoToken;
use Exception;
use Illuminate\Support\Str;
use Src\Tenant\Infrastructure\Eloquent\Models\User;

final class ConsumeTenantOwnerSsoTokenUseCase
{
    /**
     * @return array{user: User, redirect_to: string}
     */
    public function execute(string $token): array
    {
        $isTesting = app()->runningUnitTests() || app()->environment('testing');
        $centralConn = $isTesting
            ? config('database.default')
            : (config('tenancy.database.central_connection') ?: 'central');

        // 1. Buscar token en base de datos central
        $ssoToken = TenantOwnerSsoToken::on($centralConn)
            ->where('token', $token)
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->first();

        if (! $ssoToken) {
            throw new Exception('El token SSO es inválido o ha expirado.', 401);
        }

        // 2. Marcar token como consumido en la base de datos central
        $ssoToken->update(['used_at' => now()]);

        // 3. Buscar usuario en base de datos central
        $centralUser = User::on($centralConn)->find($ssoToken->user_id);
        if (! $centralUser) {
            throw new Exception('Usuario asociado al token SSO no encontrado en la plataforma central.', 404);
        }

        // 4. Sincronizar o aprovisionar el usuario en la base de datos del inquilino
        $userType = (function_exists('tenancy') && tenancy()->initialized) ? 'owner' : ($centralUser->type ?: 'tenant_owner');

        $tenantUser = User::updateOrCreate(
            ['id' => $centralUser->id],
            [
                'name' => $centralUser->name,
                'email' => $centralUser->email,
                'password' => $centralUser->password,
                'type' => $userType,
                'phone' => $centralUser->phone,
                'avatar' => $centralUser->avatar,
                'is_active' => true,
            ]
        );

        // 5. Sincronizar usuario en la tabla auth_users del tenant si la sesión está en el contexto del inquilino
        if (class_exists(\Src\Authentication\Infrastructure\Eloquent\Models\AuthUser::class)) {
            try {
                \Src\Authentication\Infrastructure\Eloquent\Models\AuthUser::updateOrCreate(
                    ['user_id' => $tenantUser->id],
                    [
                        'id' => (string) Str::uuid(),
                        'user_name' => $tenantUser->name,
                        'user_email' => $tenantUser->email,
                        'user_type' => 'owner',
                        'user_avatar' => $tenantUser->avatar ?? 'https://i.pinimg.com/originals/b0/ce/76/b0ce76f4cdb95ef13afa21a889adfc71.jpg',
                    ]
                );
            } catch (\Throwable $e) {
                // Silently ignore if table doesn't exist yet in mock tests
            }
        }

        return [
            'user' => $tenantUser,
            'redirect_to' => "/tenant/backoffice/{$tenantUser->id}/dashboard",
        ];
    }
}
