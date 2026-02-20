<?php

use Illuminate\Support\Facades\Route;
use Src\Authentication\Infrastructure\Http\Controller\CurrentUserGETController;
use Src\Authentication\Infrastructure\Http\Controller\LoginCustomerScreenGETController;
use Src\Authentication\Infrastructure\Http\Controller\LoginStaffScreenGETController;
use Src\Authentication\Infrastructure\Http\Controller\LoginWebPOSTController;
use Src\Authentication\Infrastructure\Http\Controller\LogoutWebPOSTController;

Route::get("/login",               [LoginStaffScreenGETController::class, 'index'])->name('central.web.auth.login-staff');
Route::get("/customer/login",      [LoginCustomerScreenGETController::class, 'index'])->name('central.web.auth.login-customer');

Route::post("/login",               [LoginWebPOSTController::class, "index"])->name("central.web.auth.login");
Route::post("/logout",              [LogoutWebPOSTController::class, "index"])->name("central.web.auth.logout")->middleware('auth');
Route::get("/user/{user_uuid}",     [CurrentUserGETController::class, "index"])->name("central.web.auth.user")->middleware('auth');


?>
