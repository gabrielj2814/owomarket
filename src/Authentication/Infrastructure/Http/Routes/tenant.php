<?php

use Illuminate\Support\Facades\Route;
use Src\Authentication\Infrastructure\Http\Controller\LoginStaffScreenGETController;
use Src\Authentication\Infrastructure\Http\Controller\LoginWebPOSTController;
use Src\Authentication\Infrastructure\Http\Controller\LogoutWebPOSTController;
use Src\Authentication\Infrastructure\Http\Controller\PaginaInicialTestGETController;

Route::get("login-staff",           [LoginStaffScreenGETController::class, 'index'])->name('tenant.auth.web.login-staff');

Route::post("/login",               [LoginWebPOSTController::class, "index"])->name("tenant.web.auth.login");
Route::post("/logout",              [LogoutWebPOSTController::class, "index"])->name("tenant.web.auth.logout")->middleware('auth');
Route::get("/pagina-inicial",       [PaginaInicialTestGETController::class, "index"])->name("tenant.web.inicial.page")->middleware('auth');


?>
