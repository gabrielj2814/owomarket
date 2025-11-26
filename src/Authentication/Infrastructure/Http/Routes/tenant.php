<?php

use Illuminate\Support\Facades\Route;
use Src\Authentication\Infrastructure\Http\Controller\AuthController;

Route::get("login-staff",   [AuthController::class, 'LoginStaffScreen'])->name('tenant.auth.web.login-staff');

Route::post("/login",               [AuthController::class, "loginWeb"])->name("tenant.web.auth.login");
Route::post("/logout",              [AuthController::class, "logoutWeb"])->name("tenant.web.auth.logout")->middleware('auth');
Route::get("/pagina-inicial",       [AuthController::class, "InicialPageScreen"])->name("tenant.web.inicial.page")->middleware('auth');


?>
