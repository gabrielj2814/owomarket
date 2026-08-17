<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
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

    // Route::get('/', function () {
    //     $domain = request()->getHost();
    //     return Inertia::render('welcome',[
    //         'domain' => $domain,
    //     ]);
    // })->name('tenant.welcome');

    require base_path('src/Marketplace/Infrastructure/Http/Routes/tenant.php');

    Route::prefix('auth')->group(callback: base_path('src/Authentication/Infrastructure/Http/Routes/tenant.php'));
    Route::prefix('tenant')->group(callback: base_path('src/Tenant/Infrastructure/Http/Routes/tenant.php'));
    Route::prefix('product')->group(callback: base_path('src/Product/Infrastructure/Http/Routes/tenant.php'));
    Route::prefix('category')->group(callback: base_path('src/Category/Infrastructure/Http/Routes/tenant.php'));
    Route::prefix('brand')->group(callback: base_path('src/Brand/Infrastructure/Http/Routes/tenant.php'));
    Route::prefix('attribute')->group(callback: base_path('src/Attribute/Infrastructure/Http/Routes/tenant.php'));
    Route::prefix('coupon')->group(callback: base_path('src/Coupon/Infrastructure/Http/Routes/tenant.php'));
    Route::prefix('tax')->group(callback: base_path('src/Tax/Infrastructure/Http/Routes/tenant.php'));
    Route::prefix('shipping')->group(callback: base_path('src/Shipping/Infrastructure/Http/Routes/tenant.php'));
});
