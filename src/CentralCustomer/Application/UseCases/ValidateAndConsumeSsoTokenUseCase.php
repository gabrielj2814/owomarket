<?php

declare(strict_types=1);

namespace Src\CentralCustomer\Application\UseCases;

use Exception;
use Illuminate\Support\Str;
use Src\CentralCustomer\Infrastructure\Eloquent\Models\CentralCustomerSsoToken;
use Src\Customer\Infrastructure\Eloquent\Models\Customer as TenantCustomer;

final class ValidateAndConsumeSsoTokenUseCase
{
    /**
     * @return array{customer: TenantCustomer, central_customer: array<string, mixed>, addresses: array<int, mixed>}
     */
    public function execute(string $token, ?string $currentDomain = null): array
    {
        $token = trim($token);

        // Hallazgo C5: leer, comprobar `used_at` y escribir eran TRES sentencias sueltas,
        // asi que dos peticiones simultaneas con el mismo enlace pasaban ambas la
        // comprobacion y lo consumian dos veces (replay por carrera). Curiosamente el
        // archivo ya importaba `DB` y no lo usaba.
        //
        // Ahora la comprobacion y el consumo son la MISMA sentencia, y se mira el numero
        // de filas afectadas: si es 0, el token no existe, ya se uso o expiro.
        // Se usa el query builder del propio modelo para heredar su conexion: en
        // produccion la central, en tests la de la suite.
        $consumed = CentralCustomerSsoToken::query()
            ->where('token', $token)
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->update(['used_at' => now()]);

        if ($consumed === 0) {
            throw new Exception('El enlace de inicio de sesión es inválido, ya fue utilizado o ha expirado.', 410);
        }

        $ssoToken = CentralCustomerSsoToken::with('customer.addresses')
            ->where('token', $token)
            ->first();

        if (! $ssoToken) {
            throw new Exception('Token de inicio de sesión no encontrado o inválido.', 404);
        }

        // Hallazgo A8: este metodo RECIBIA `$currentDomain` y no lo usaba nunca, y el
        // `target_domain` que se persiste al generar el token jamas se comparaba con
        // nada. El duenno de la tienda A pedia un token legitimo para su tienda y lo
        // abria en `https://tiendaB.owomarket.com/...`: el token era valido, asi que
        // quedaba logueado en una tienda ajena. Rotura del aislamiento multi-tenant.
        if ($currentDomain !== null && $ssoToken->target_domain !== null) {
            if (! hash_equals((string) $ssoToken->target_domain, $currentDomain)) {
                throw new Exception('Este enlace de inicio de sesión no es válido para esta tienda.', 403);
            }
        }

        $centralCustomer = $ssoToken->customer;
        if (! $centralCustomer || ! $centralCustomer->is_active) {
            throw new Exception('La cuenta del usuario central se encuentra inactiva.', 403);
        }

        // Sincronizar o crear en la base de datos del tenant actual
        $tenantCustomer = TenantCustomer::where('central_uuid', $centralCustomer->id)->first()
            ?? TenantCustomer::where('email', $centralCustomer->email)->first();

        if ($tenantCustomer) {
            $tenantCustomer->update([
                'central_uuid' => $centralCustomer->id,
                'name' => $centralCustomer->name,
                'email' => $centralCustomer->email,
                'phone' => $centralCustomer->phone ?? $tenantCustomer->phone,
                'is_active' => true,
            ]);
        } else {
            $tenantCustomer = TenantCustomer::create([
                'id' => (string) Str::uuid(),
                'central_uuid' => $centralCustomer->id,
                'name' => $centralCustomer->name,
                'email' => $centralCustomer->email,
                'phone' => $centralCustomer->phone,
                'is_active' => true,
                'accepts_marketing' => false,
            ]);
        }

        return [
            'customer' => $tenantCustomer,
            'central_customer' => [
                'id' => $centralCustomer->id,
                'name' => $centralCustomer->name,
                'email' => $centralCustomer->email,
                'phone' => $centralCustomer->phone,
                'document_id' => $centralCustomer->document_id,
                'avatar' => $centralCustomer->avatar,
            ],
            'addresses' => $centralCustomer->addresses->toArray(),
        ];
    }
}
