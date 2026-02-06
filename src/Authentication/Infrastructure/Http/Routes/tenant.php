<?php

use Illuminate\Support\Facades\Route;
use Src\Authentication\Infrastructure\Http\Controller\CurrentUserGETController;
use Src\Authentication\Infrastructure\Http\Controller\LoginStaffScreenGETController;
use Src\Authentication\Infrastructure\Http\Controller\LoginTenantScreenGETController;
use Src\Authentication\Infrastructure\Http\Controller\LoginWebPOSTController;
use Src\Authentication\Infrastructure\Http\Controller\LoginWebTenantPOSTController;
use Src\Authentication\Infrastructure\Http\Controller\LogoutWebPOSTController;
use Src\Authentication\Infrastructure\Http\Controller\PaginaInicialTestGETController;

Route::get("login",                 [LoginTenantScreenGETController::class, 'index'])->name('tenant.web.auth.login-tenant');

Route::post("/login",               [LoginWebTenantPOSTController::class, "index"])->name("tenant.web.auth.login");
Route::post("/logout",              [LogoutWebPOSTController::class, "index"])->name("tenant.web.auth.logout")->middleware('auth');
Route::get("/pagina-inicial",       [PaginaInicialTestGETController::class, "index"])->name("tenant.web.inicial.page")->middleware('auth');
Route::get("/user/{user_uuid}",     [CurrentUserGETController::class, "index"])->name("tenant.web.auth.user")->middleware('auth');


?>
