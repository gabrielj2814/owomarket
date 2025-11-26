<?php

use Illuminate\Support\Facades\Route;
use Src\Authentication\Infrastructure\Http\Controller\AuthController;

Route::get("login-staff", [AuthController::class, 'LoginStaffScreen'])->name('central.auth.web.login-staff');

Route::post("/login",       [AuthController::class, "loginWeb"])->name("web.auth.login");
// Route::post("/login",       [AuthController::class, "loginApi"])->name("web.auth.login");
// Route::post("/logout",      [AuthController::class, "logoutApi"])->name("web.auth.logout");


?>
