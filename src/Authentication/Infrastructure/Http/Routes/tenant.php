<?php

use Illuminate\Support\Facades\Route;
use Src\Authentication\Infrastructure\Http\Controller\CurrentUserGETController;
use Src\Authentication\Infrastructure\Http\Controller\LoginTenantScreenGETController;
use Src\Authentication\Infrastructure\Http\Controller\LoginWebTenantPOSTController;
use Src\Authentication\Infrastructure\Http\Controller\LogoutWebPOSTController;
use Src\Authentication\Infrastructure\Http\Controller\PaginaInicialTestGETController;

Route::get('login', [LoginTenantScreenGETController::class, 'index'])->name('tenant.web.auth.login-tenant');
// Hallazgo N18: consumir un token SSO es canjear una credencial de un solo uso.
Route::get('sso-consume', \Src\Tenant\Infrastructure\Http\Controller\ConsumeTenantOwnerSsoTokenGETController::class)
    ->name('tenant.web.auth.sso-consume')
    ->middleware('throttle:sso');

// Hallazgo N18: sin limite se podia probar contrasenas a ritmo de maquina.
Route::post('/login', [LoginWebTenantPOSTController::class, 'index'])
    ->name('tenant.web.auth.login')
    ->middleware('throttle:credenciales');
Route::post('/logout', [LogoutWebPOSTController::class, 'index'])->name('tenant.web.auth.logout')->middleware('auth');
Route::get('/pagina-inicial', [PaginaInicialTestGETController::class, 'index'])->name('tenant.web.inicial.page')->middleware('auth');
Route::get('/user/{user_uuid}', [CurrentUserGETController::class, 'index'])->name('tenant.web.auth.user')->middleware('auth');
