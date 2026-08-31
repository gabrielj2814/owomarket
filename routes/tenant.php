<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;

/*
|--------------------------------------------------------------------------
| Tenant Routes
|--------------------------------------------------------------------------
|
| Here you can register the tenant routes for your application.
| These routes are loaded by the TenantRouteServiceProvider.
|
| Feel free to customize them however you want. Good luck!
|
*/

Route::middleware([
    'web',
    InitializeTenancyByDomain::class,
    PreventAccessFromCentralDomains::class,
])->group(function () {

    require base_path('src/Marketplace/Infrastructure/Http/Routes/tenant.php');
    Route::get('/login', fn () => redirect('/auth/login'))->name('tenant.login.alias');

    Route::prefix('auth')->group(callback: base_path('src/Authentication/Infrastructure/Http/Routes/tenant.php'));
    // NOTA: el backoffice central de SuperAdmin (src/Admin/.../Routes/web.php) NO se monta aquí.
    // Se registra únicamente en routes/web.php, dentro del grupo Route::domain(central_domain).
    // Montarlo en el grupo de tenancy exponía payouts, roles RBAC, impersonación de tiendas y
    // la pista de auditoría en cada subdominio de tienda, protegidos sólo por 'auth' (sin rol).
    /*
    | Fase 5 — el backoffice de una tienda suspendida no se abre.
    |
    | Fuera del grupo quedan, a proposito, las dos cosas que tienen que seguir vivas: el
    | escaparate --que se monta arriba, con `require`-- y `auth`, porque un comerciante que no
    | puede ni iniciar sesion no llega a leer el motivo de su suspension.
    */
    Route::middleware('tenant_active')->group(function () {
        Route::prefix('tenant')->group(callback: base_path('src/Tenant/Infrastructure/Http/Routes/tenant.php'));
        Route::prefix('product')->group(callback: base_path('src/Product/Infrastructure/Http/Routes/tenant.php'));
        Route::prefix('category')->group(callback: base_path('src/Category/Infrastructure/Http/Routes/tenant.php'));
        Route::prefix('brand')->group(callback: base_path('src/Brand/Infrastructure/Http/Routes/tenant.php'));
        Route::prefix('attribute')->group(callback: base_path('src/Attribute/Infrastructure/Http/Routes/tenant.php'));
        Route::prefix('coupon')->group(callback: base_path('src/Coupon/Infrastructure/Http/Routes/tenant.php'));
        Route::prefix('tax')->group(callback: base_path('src/Tax/Infrastructure/Http/Routes/tenant.php'));
        Route::prefix('shipping')->group(callback: base_path('src/Shipping/Infrastructure/Http/Routes/tenant.php'));
        Route::prefix('billing')->group(callback: base_path('src/Billing/Infrastructure/Http/Routes/tenant.php'));
        Route::prefix('customer')->group(callback: base_path('src/Customer/Infrastructure/Http/Routes/tenant.php'));
        Route::prefix('order')->group(callback: base_path('src/Order/Infrastructure/Http/Routes/tenant.php'));
        Route::prefix('review')->group(callback: base_path('src/Review/Infrastructure/Http/Routes/tenant.php'));
        Route::prefix('settings')->group(callback: base_path('src/TenantSettings/Infrastructure/Http/Routes/tenant.php'));
        Route::prefix('monetization')->group(callback: base_path('src/Monetization/Infrastructure/Http/Routes/tenant.php'));
        Route::prefix('support')->group(callback: base_path('src/SupportTicket/Infrastructure/Http/Routes/tenant.php'));
    });
});
