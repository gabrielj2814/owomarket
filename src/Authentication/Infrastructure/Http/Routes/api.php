<?php

use Illuminate\Support\Facades\Route;
use Src\Authentication\Infrastructure\Http\Controller\AuthController;

Route::get("/helpcheck",    [AuthController::class, "helpCheck"])->name("api.auth.helpCheck");
Route::post("/login",       [AuthController::class, "loginApi"])->name("api.auth.login");
Route::post("/logout",      [AuthController::class, "logoutApi"])->name("api.auth.logout");


?>
