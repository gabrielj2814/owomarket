<?php

declare(strict_types=1);

namespace Src\Shared\Infrastructure\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Src\Shared\Domain\ValueObjects\UserType;
use Src\Shared\Helper\ApiResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Restringe el acceso a los super administradores de la plataforma central.
 *
 * Alias registrado en bootstrap/app.php: 'super_admin'
 *
 * Uso:
 *   Route::post('/api/security/roles', Controller::class)->middleware(['auth', 'super_admin']);
 *
 * Debe aplicarse SIEMPRE después de 'auth'. Si se usa sin 'auth' delante, responde 401
 * en lugar de dejar pasar la petición.
 */
final class EnsureUserIsSuperAdmin
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

        if (($user->type ?? null) !== UserType::SUPER_ADMIN) {
            return $this->deny($request, 'No tienes permisos de super administrador.', 403);
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
