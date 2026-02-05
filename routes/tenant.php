<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Src\Authentication\Infrastructure\Http\Controller\AuthController;
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

    Route::get('/', function () {
        $domain = request()->getHost();
        return Inertia::render('welcome',[
            'domain' => $domain,
        ]);
    })->name('tenant.welcome');

    // Route::get("auth/login-staff", [AuthController::class, 'LoginStaffScreen'])->name('tenant.auth.web.login-staff');

    Route::prefix("auth")->group(callback: base_path("src/Authentication/Infrastructure/Http/Routes/tenant.php"));
    Route::prefix("tenant")->group(callback: base_path("src/Tenant/Infrastructure/Http/Routes/tenant.php"));

});
