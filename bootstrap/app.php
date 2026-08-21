<?php

use App\Http\Middleware\CorsHeaders;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Support\Facades\Route;
use Src\Shared\Infrastructure\Http\Middleware\EnsureSupportRequesterIsAuthenticated;
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
                InitializeTenancyByDomain::class,
                PreventAccessFromCentralDomains::class,
            ])->prefix('api-tenant')->group(base_path('routes/tenantApi.php'));

        // // O si quieres separar tenant web de tenant api:
        // Route::middleware('web')
        //     ->group(base_path('routes/tenant-web.php'));
        }
    )
    ->withMiddleware(function (Middleware $middleware): void {

        $middleware->web(append: [
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
            CorsHeaders::class

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
            'internal' => InternalServiceMiddleware::class,
            'support_session' => EnsureSupportRequesterIsAuthenticated::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
})->create();
