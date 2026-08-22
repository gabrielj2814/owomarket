<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Da a cada dominio su propia cookie de sesion (hallazgo F3).
 *
 * `config/session.php` fijaba `'domain' => env('SESSION_DOMAIN', '.owomarket.local')`: un
 * comodin para **todos** los subdominios, con un unico nombre de cookie para toda la
 * aplicacion. Y `StartSession` (del grupo `web`) corre ANTES de
 * `InitializeTenancyByDomain`, asi que el ID de sesion que se lee es el mismo en todos los
 * dominios: un usuario autenticado en `tienda-a` navegaba a `tienda-b`, el navegador
 * mandaba la misma cookie, y la sesion se leia de una base de datos y se persistia en
 * otra. Sesiones colgadas o reutilizadas entre inquilinos.
 *
 * Este middleware se **antepone** a todo el resto para poder actuar antes de que
 * `StartSession` lea la cookie: despues ya seria tarde.
 *
 * No depende de la tenancy —que todavia no se ha inicializado— sino del host, que es
 * precisamente el discriminante que usa `InitializeTenancyByDomain`.
 */
final class ScopeSessionCookieToHost
{
    public function handle(Request $request, Closure $next): Response
    {
        $host = $request->getHost();
        $sufijo = substr(sha1($host), 0, 12);

        config([
            // Nombre por host: aunque alguien vuelva a poner un `SESSION_DOMAIN` comodin,
            // las sesiones de dos dominios ya no se pisan.
            'session.cookie' => config('session.cookie').'_'.$sufijo,
            // Y la cookie deja de ofrecerse a los subdominios hermanos.
            'session.domain' => null,
        ]);

        return $next($request);
    }
}
