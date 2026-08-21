<?php

declare(strict_types=1);

namespace Src\Shared\Infrastructure\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Src\Shared\Domain\ValueObjects\UserType;
use Src\Shared\Helper\ApiResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Restringe el acceso a los propietarios de tienda (y al super administrador).
 *
 * Alias registrado en bootstrap/app.php: 'tenant_owner'
 *
 * Uso:
 *   Route::post('/owner/api/payout-request', Controller::class)->middleware(['auth', 'tenant_owner']);
 *
 * Acepta tanto 'tenant_owner' (identidad en la base de datos central) como 'owner'
 * (identidad aprovisionada dentro de la base de datos del inquilino), porque el mismo
 * propietario tiene un valor distinto en `type` según el contexto en el que inició sesión.
 *
 * OJO — este middleware comprueba el ROL, no la PROPIEDAD. No verifica que el usuario
 * sea dueño del `tenant_id` concreto que viaja en la petición. Esa comprobación debe
 * hacerla el caso de uso correspondiente (ver Fase 0.3 del plan de correcciones).
 */
final class EnsureUserIsTenantOwner
{
    /**
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null) {
            return $this->deny($request, 'Debes iniciar sesión para acceder a este recurso.', 401);
        }

        if (($user->is_active ?? true) === false) {
            return $this->deny($request, 'Tu cuenta está desactivada.', 403);
        }

        $allowed = [
            UserType::SUPER_ADMIN,
            UserType::TENANT_OWNER,
            UserType::OWNER,
        ];

        if (! in_array($user->type ?? null, $allowed, true)) {
            return $this->deny($request, 'Esta sección es exclusiva de los propietarios de tienda.', 403);
        }

        return $next($request);
    }

    private function deny(Request $request, string $message, int $code): Response
    {
        if ($request->expectsJson()) {
            return ApiResponse::error(message: $message, code: $code);
        }

        abort($code, $message);
    }
}
