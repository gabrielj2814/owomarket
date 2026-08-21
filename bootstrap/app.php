<?php

use App\Http\Middleware\CorsHeaders;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Support\Facades\Route;
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
              Route::middleware([
                'api',
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
        |
        */
        $middleware->alias([
            'super_admin' => EnsureUserIsSuperAdmin::class,
            'staff' => EnsureUserHasStaffPermission::class,
            'tenant_owner' => EnsureUserIsTenantOwner::class,
            'internal' => InternalServiceMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
})->create();
