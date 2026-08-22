<?php

declare(strict_types=1);

namespace Src\Shared\Infrastructure\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Src\Shared\Domain\ValueObjects\UserType;
use Src\Shared\Helper\ApiResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Control de rol DENTRO de una tienda (hallazgo N19).
 *
 * Alias registrado en bootstrap/app.php: 'tenant_can'
 *
 * Uso:
 *   Route::prefix('product')->middleware('tenant_can:manage_catalog')->group(...);
 *   Route::prefix('billing')->middleware('tenant_can:manage_billing,manage_orders')->group(...);
 *
 * Con varios permisos la lógica es OR: basta con tener uno.
 *
 * Hasta ahora `/api-tenant/*` llevaba 'web' + tenancy + 'auth' y nada más. Cualquiera con
 * sesión en la tienda —incluido un `staff` recién contratado— podía borrar el catálogo
 * entero o anular facturas exactamente igual que el propietario. La Fase 4.2 dejó las
 * tablas de permisos en cada base de inquilino (hallazgo F5); esto es lo que las usa.
 *
 * **Las lecturas pasan siempre.** El middleware sólo exige permiso en los métodos que
 * escriben. Un `staff` tiene que poder consultar el catálogo, los pedidos y la
 * facturación para hacer su trabajo; lo que no puede es modificarlos sin que se lo hayan
 * concedido. Separarlo aquí evita duplicar cada grupo de rutas en uno de lectura y otro
 * de escritura.
 *
 * **El propietario pasa siempre.** Es el dueño de la tienda: no hay ningún permiso que se
 * le pueda negar, y hacerle depender de filas en `model_has_roles` sólo abriría la puerta
 * a que un fallo de aprovisionamiento lo dejara fuera de su propio negocio.
 */
final class EnsureTenantUserHasPermission
{
    /**
     * Métodos que no modifican nada. `OPTIONS` entra porque es el preflight de CORS:
     * responderle 403 rompe la petición real que viene detrás.
     */
    private const METODOS_DE_LECTURA = ['GET', 'HEAD', 'OPTIONS'];

    /**
     * Últimos segmentos de ruta que son lectura aunque lleguen por POST.
     *
     * **Esta API lista con POST.** Los doce `/{modulo}/filter` mandan los criterios en el
     * cuerpo, y los `calculate` de envío e impuestos son presupuestos que no escriben nada.
     * Mirar sólo el verbo dejaba a un `staff` sin poder ver ni un listado: la regla de «las
     * lecturas pasan siempre» no servía de nada porque aquí leer es un POST.
     *
     * Se detectó al comprobar en un navegador que el backoffice se quedaba vacío.
     *
     * La lista es explícita y corta a propósito. Un patrón amplio —«todo POST que empiece
     * por filter»— acabaría dejando pasar una escritura el día que alguien llame
     * `filter-and-archive` a algo.
     */
    private const POST_DE_LECTURA = ['filter', 'calculate'];

    public function handle(Request $request, Closure $next, string ...$permisos): Response
    {
        if ($this->esLectura($request)) {
            return $next($request);
        }

        $user = $request->user();

        if ($user === null) {
            // Igual que 'super_admin': si alguien lo coloca sin 'auth' delante, esto
            // responde 401 en vez de dejar pasar la petición.
            return $this->denegar($request, 'Debes iniciar sesión para acceder a este recurso.', 401);
        }

        if (($user->is_active ?? true) === false) {
            return $this->denegar($request, 'Tu cuenta está desactivada.', 403);
        }

        if ($this->esPropietario($user)) {
            return $next($request);
        }

        if ($permisos !== [] && method_exists($user, 'hasAnyPermission') && $user->hasAnyPermission($permisos)) {
            return $next($request);
        }

        return $this->denegar($request, 'Tu rol en esta tienda no permite realizar esta acción.', 403);
    }

    private function esLectura(Request $request): bool
    {
        if (in_array($request->getMethod(), self::METODOS_DE_LECTURA, true)) {
            return true;
        }

        $ultimoSegmento = last(explode('/', (string) $request->route()?->uri()));

        return $request->isMethod('POST') && in_array($ultimoSegmento, self::POST_DE_LECTURA, true);
    }

    /**
     * Dentro de la base del inquilino el rol vive en `users.type`. Se aceptan también los
     * tipos centrales porque un propietario que entra por SSO desde el hub llega con el
     * `tenant_owner` de la base central.
     */
    private function esPropietario(mixed $user): bool
    {
        return in_array((string) ($user->type ?? ''), [
            UserType::OWNER,
            UserType::TENANT_OWNER,
            UserType::SUPER_ADMIN,
        ], true);
    }

    private function denegar(Request $request, string $mensaje, int $codigo): Response
    {
        if ($request->expectsJson()) {
            return ApiResponse::error(message: $mensaje, code: $codigo);
        }

        abort($codigo, $mensaje);
    }
}
