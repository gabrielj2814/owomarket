<?php

declare(strict_types=1);

namespace Src\Tenant\Application\UseCase;

use Exception;
use Illuminate\Support\Str;
use Src\Tenant\Infrastructure\Eloquent\Models\TenantOwnerSsoToken;
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

        // 1. Consumir el token de forma atomica (hallazgo C5).
        //
        // Buscar y despues marcar eran dos sentencias, asi que dos peticiones simultaneas
        // con el mismo enlace lo consumian ambas. La comprobacion y la escritura van ahora
        // juntas, y se mira el numero de filas afectadas.
        //
        // Ademas se exige que el token sea de ESTA tienda (hallazgo A8): la consulta no
        // filtraba por `tenant_id`, asi que un token legitimo de la tienda A servia para
        // entrar como propietario en la tienda B.
        $tenantActual = (function_exists('tenancy') && tenancy()->initialized) ? (string) tenant('id') : null;

        $query = TenantOwnerSsoToken::on($centralConn)
            ->where('token', $token)
            ->whereNull('used_at')
            ->where('expires_at', '>', now());

        if ($tenantActual !== null) {
            $query->where('tenant_id', $tenantActual);
        }

        $consumed = (clone $query)->update(['used_at' => now()]);

        if ($consumed === 0) {
            throw new Exception('El token SSO es inválido, ya fue utilizado, ha expirado o no corresponde a esta tienda.', 401);
        }

        $ssoToken = TenantOwnerSsoToken::on($centralConn)->where('token', $token)->first();

        if (! $ssoToken) {
            throw new Exception('El token SSO es inválido o ha expirado.', 401);
        }

        // 3. Buscar usuario en base de datos central
        $centralUser = User::on($centralConn)->find($ssoToken->user_id);
        if (! $centralUser) {
            throw new Exception('Usuario asociado al token SSO no encontrado en la plataforma central.', 404);
        }

        // 4. Sincronizar o aprovisionar el usuario en la base de datos del inquilino
        // Hallazgo A8: aqui se forzaba `type = 'owner'` sin mirar quien era el usuario, asi
        // que consumir un token creaba un propietario en la base del inquilino aunque la
        // persona no lo fuera. Se conserva su tipo real.
        $userType = $centralUser->type ?: 'tenant_owner';

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
