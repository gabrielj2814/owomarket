<?php

use App\Http\Middleware\CorsHeaders;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\ScopeSessionCookieToHost;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Support\Facades\Route;
use Src\Shared\Infrastructure\Http\Middleware\EnsureSupportRequesterIsAuthenticated;
use Src\Shared\Infrastructure\Http\Middleware\EnsureRouteUserIsSelf;
use Src\Shared\Infrastructure\Http\Middleware\EnsureTenantUserHasPermission;
use Src\Shared\Infrastructure\Http\Middleware\EnsureUserHasStaffPermission;
use Src\Shared\Infrastructure\Http\Middleware\EnsureUserIsSuperAdmin;
use Src\Shared\Infrastructure\Http\Middleware\EnsureUserIsTenantOwner;
use Src\Shared\Infrastructure\Http\Middleware\InternalServiceMiddleware;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            /*
            |--------------------------------------------------------------
            | API del inquilino (/api-tenant/*)
            |--------------------------------------------------------------
            |
            | Hasta la Fase 0.3-E este grupo usaba 'api', que en Laravel 11+
            | está vacío por defecto: sin sesión, sin CSRF y —sobre todo— sin
            | nada sobre lo que 'auth' pudiera resolver una identidad. El
            | resultado era que las ~108 rutas del backoffice de cada tienda
            | quedaban abiertas a internet (hallazgo A5): crear cupones del
            | 100%, borrar el catálogo o leer la facturación sin login.
            |
            | Se cambia a 'web' —el mismo grupo que ya usa routes/tenant.php—
            | para tener StartSession y VerifyCsrfToken. El orden importa:
            | la sesión arranca antes que la tenancy, igual que en
            | routes/tenant.php, y 'auth' se aplica DESPUÉS de
            | InitializeTenancyByDomain (ver routes/tenantApi.php) para que
            | el usuario se resuelva contra la base de datos del inquilino.
            |
            */
            Route::middleware([
                'web',
                // Hallazgo N18: techo general para la API de la tienda. Es alto a
                // proposito —no es la defensa contra abuso dirigido, para eso estan los
                // limitadores con nombre de RouteServiceProvider— sino el tope que impide
                // que un bucle roto en el navegador o un raspador tumben la base.
                'throttle:api',
                InitializeTenancyByDomain::class,
                PreventAccessFromCentralDomains::class,
            ])->prefix('api-tenant')->group(base_path('routes/tenantApi.php'));

            // // O si quieres separar tenant web de tenant api:
            // Route::middleware('web')
            //     ->group(base_path('routes/tenant-web.php'));
        }
    )
    ->withMiddleware(function (Middleware $middleware): void {

        /*
        |----------------------------------------------------------------------
        | CSRF: excepcion para la comunicacion interna entre servicios
        |----------------------------------------------------------------------
        |
        | La Fase 0.3-E movio `/api-tenant/*` del grupo 'api' al 'web' para que
        | hubiera sesion y 'auth' pudiera resolver identidad (hallazgo A5). Con
        | ello entro tambien `VerifyCsrfToken`, y las rutas `interna/*` —que son
        | llamadas servidor-a-servidor, sin navegador ni sesion— empezaron a
        | responder 419.
        |
        | Consecuencia real: **el login del dominio de tienda dejo de funcionar**,
        | porque `LoginWebTenantPOSTController` consulta al usuario por email a
        | traves de `/api-tenant/user/interna/consulta-usuario-por-email`.
        |
        | Exceptuarlas es correcto: CSRF protege peticiones autenticadas por
        | cookie de sesion, y estas se autentican con el secreto compartido que
        | valida `InternalServiceMiddleware`. El equivalente central nunca fallo
        | porque `routes/api.php` sigue en el grupo 'api', que no lleva CSRF.
        |
        | El patron es deliberadamente estrecho: solo el segmento `interna`.
        */
        $middleware->validateCsrfTokens(except: [
            'api-tenant/*/interna/*',
        ]);

        // Hallazgo F3: tiene que correr ANTES de `StartSession`, que es quien lee la
        // cookie. Por eso se antepone a toda la pila en vez de anadirse al grupo `web`.
        $middleware->prepend(ScopeSessionCookieToHost::class);

        // Hallazgo N18: el grupo 'api' de Laravel 11+ viene vacio, asi que /api/* no
        // tenia ningun limite. Mismo techo que /api-tenant/*.
        $middleware->api(append: [
            'throttle:api',
        ]);

        $middleware->web(append: [
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
            CorsHeaders::class,

        ]);

        /*
        |----------------------------------------------------------------------
        | Alias de middleware de autorización
        |----------------------------------------------------------------------
        |
        | Se aplican SIEMPRE después de 'auth', que resuelve la identidad.
        | Estos alias resuelven el rol y los permisos.
        |
        |   'super_admin'  → sólo usuarios con type = 'super_admin'
        |   'staff'        → staff central con permiso RBAC (spatie/laravel-permission);
        |                    acepta parámetros: staff:manage_payouts,manage_plans (lógica OR).
        |                    El super administrador siempre pasa.
        |   'tenant_owner' → propietarios de tienda ('tenant_owner' u 'owner') y super admin.
        |   'own_user'     → el uuid que viaja en la URL tiene que ser el de quien pide
        |                    (hallazgos P1 y P2). Acepta el nombre del segmento como
        |                    parametro: own_user:user_uuid es el valor por defecto.
        |   'tenant_can'   → permisos DENTRO de una tienda (hallazgo N19). Acepta
        |                    parametros: tenant_can:manage_catalog,manage_orders (OR).
        |                    Las lecturas pasan siempre; solo exige permiso al escribir.
        |                    El propietario de la tienda pasa siempre.
        |   'internal'     → comunicación entre servicios internos mediante secreto compartido.
        |   'support_session' → exige sesión de propietario de tienda (guard 'web') o de
        |                    cliente central (session('central_customer_id')). No resuelve
        |                    identidad, sólo exige que exista una de las dos.
        |
        */
        $middleware->alias([
            'super_admin' => EnsureUserIsSuperAdmin::class,
            'staff' => EnsureUserHasStaffPermission::class,
            'tenant_owner' => EnsureUserIsTenantOwner::class,
            'tenant_can' => EnsureTenantUserHasPermission::class,
            'own_user' => EnsureRouteUserIsSelf::class,
            'internal' => InternalServiceMiddleware::class,
            'support_session' => EnsureSupportRequesterIsAuthenticated::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
