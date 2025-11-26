<?php

use Illuminate\Support\Facades\Route;
use Src\Authentication\Infrastructure\Http\Controller\AuthController;

Route::get("login-staff", [AuthController::class, 'LoginStaffScreen'])->name('central.auth.web.login-staff');

Route::post("/login",               [AuthController::class, "loginWeb"])->name("central.web.auth.login");
Route::post("/logout",              [AuthController::class, "logoutWeb"])->name("central.web.auth.logout")->middleware('auth');
Route::get("/pagina-inicial",       [AuthController::class, "InicialPageScreen"])->name("central.web.inicial.page")->middleware('auth');


?>
