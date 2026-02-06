<?php

use App\Http\Middleware\CorsHeaders;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\HandleInertiaTenancy;
use App\Http\Middleware\TenantAuthentication;
use App\Http\Middleware\VerifyCsrfToken;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Support\Facades\Route;
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
            HandleInertiaTenancy::class,
            CorsHeaders::class

        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
})->create();
