<?php

declare(strict_types=1);

namespace Src\Shared\Infrastructure\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Src\Shared\Domain\ValueObjects\UserType;
use Src\Shared\Helper\ApiResponse;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Restringe el acceso del staff central según los permisos RBAC de spatie/laravel-permission.
 *
 * Alias registrado en bootstrap/app.php: 'staff'
 *
 * Uso:
 *   Route::get('/api/payouts', Controller::class)->middleware(['auth', 'staff:manage_payouts']);
 *   Route::get('/api/orders', Controller::class)->middleware(['auth', 'staff:manage_orders,manage_customers']);
 *
 * Con varios permisos separados por coma basta con tener UNO de ellos (lógica OR).
 *
 * Los permisos disponibles los define ListStaffRolesAndPermissionsUseCase::ensureDefaultPermissionsAndRolesExist():
 *   manage_tenants, manage_orders, manage_customers, manage_payouts, manage_support,
 *   manage_catalog, manage_moderation, manage_cms, manage_plans, manage_staff_roles, view_audit_logs
 *
 * IMPORTANTE — los usuarios con type = 'super_admin' pasan siempre, sin consultar la tabla
 * de permisos. Esto es deliberado: las tablas de Spatie sólo existen en la base de datos
 * central y los roles se crean de forma perezosa la primera vez que alguien abre la pantalla
 * de roles, así que sin este atajo un superadmin recién sembrado quedaría fuera de su propio panel.
 */
final class EnsureUserHasStaffPermission
{
    /**
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        $user = $request->user();

        if ($user === null) {
            return $this->deny($request, 'Debes iniciar sesión para acceder a este recurso.', 401);
        }

        if (($user->is_active ?? true) === false) {
            return $this->deny($request, 'Tu cuenta está desactivada.', 403);
        }

        // Atajo: el super administrador tiene acceso total y no depende de la tabla de permisos.
        if (($user->type ?? null) === UserType::SUPER_ADMIN) {
            return $next($request);
        }

        // Sin permisos declarados en la ruta, basta con ser staff autenticado del dominio central.
        if ($permissions === []) {
            return $next($request);
        }

        if (! $this->userHasAnyPermission($user, $permissions)) {
            return $this->deny(
                $request,
                'No tienes permisos suficientes para realizar esta acción.',
                403
            );
        }

        return $next($request);
    }

    /**
     * @param  array<int, string>  $permissions
     */
    private function userHasAnyPermission(mixed $user, array $permissions): bool
    {
        foreach ($permissions as $permission) {
            $permission = trim($permission);

            if ($permission === '') {
                continue;
            }

            try {
                if ($user->can($permission)) {
                    return true;
                }
            } catch (Throwable $e) {
                // Las tablas de spatie/laravel-permission sólo existen en la base de datos
                // central. Si esta ruta se alcanza dentro del contexto de un tenant, la
                // consulta falla. Se deniega el acceso (nunca se concede) y se deja rastro,
                // porque llegar aquí significa que una ruta central está montada donde no debe.
                Log::warning('[staff middleware] No se pudo evaluar el permiso RBAC.', [
                    'permission' => $permission,
                    'user_id' => $user->id ?? null,
                    'host' => request()->getHost(),
                    'exception' => $e->getMessage(),
                ]);

                return false;
            }
        }

        return false;
    }

    private function deny(Request $request, string $message, int $code): Response
    {
        if ($request->expectsJson()) {
            return ApiResponse::error(message: $message, code: $code);
        }

        abort($code, $message);
    }
}
