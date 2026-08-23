<?php

use Illuminate\Support\Facades\Route;
use Src\Authentication\Infrastructure\Http\Controller\CurrentUserGETController;
use Src\Authentication\Infrastructure\Http\Controller\LoginStaffScreenGETController;
use Src\Authentication\Infrastructure\Http\Controller\LoginWebPOSTController;
use Src\Authentication\Infrastructure\Http\Controller\LogoutWebPOSTController;

Route::get('/login', [LoginStaffScreenGETController::class, 'index'])->name('central.web.auth.login-staff');
Route::get('/sso-consume', \Src\Tenant\Infrastructure\Http\Controller\ConsumeTenantOwnerSsoTokenGETController::class)
    ->name('central.web.auth.sso-consume')
    ->middleware('throttle:sso');

// Hallazgo N18: el login del backoffice central no tenia ningun freno.
Route::post('/login', [LoginWebPOSTController::class, 'index'])
    ->name('central.web.auth.login')
    ->middleware('throttle:credenciales');
Route::post('/logout', [LogoutWebPOSTController::class, 'index'])->name('central.web.auth.logout')->middleware('auth');
Route::get('/user/{user_uuid}', [CurrentUserGETController::class, 'index'])->name('central.web.auth.user')->middleware('auth');
