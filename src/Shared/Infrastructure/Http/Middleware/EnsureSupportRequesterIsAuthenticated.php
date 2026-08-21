<?php

declare(strict_types=1);

namespace Src\Shared\Infrastructure\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Src\Shared\Helper\ApiResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Exige una identidad real de sesión para las APIs públicas de la mesa de
 * soporte central (hallazgo A6 de la auditoría del 21/08/2026).
 *
 * La mesa de soporte tiene dos tipos de solicitante legítimo, autenticados
 * por mecanismos distintos:
 *
 *   - Propietario de tienda: sesión estándar de Laravel (guard 'web').
 *   - Cliente central: NO pasa por un guard de Auth. No existe el guard
 *     'central_customer' en config/auth.php (hallazgo F4); su identidad vive
 *     en session('central_customer_id'), asignada por ConsumeSsoTokenPOSTController
 *     al consumir el token SSO.
 *
 * Este middleware sólo verifica que exista UNA de las dos sesiones. La
 * resolución del ID real y su tipo la hace cada controlador con el trait
 * ResolvesSupportRequester — nunca a partir de datos que llegan en el
 * request (eso era exactamente el vector de IDOR del hallazgo A6).
 *
 * Alias registrado en bootstrap/app.php: 'support_session'
 */
final class EnsureSupportRequesterIsAuthenticated
{
    /**
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $hasTenantOwnerSession = $request->user() !== null;
        $hasCustomerSession = $request->session()->has('central_customer_id');

        if (! $hasTenantOwnerSession && ! $hasCustomerSession) {
            return ApiResponse::error('Debes iniciar sesión para acceder a soporte.', 401);
        }

        return $next($request);
    }
}
