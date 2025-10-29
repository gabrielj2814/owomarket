<?php

use App\Modules\Core\Auth\Controller\AuthController;
use Illuminate\Support\Facades\Route;

Route::get("/helpcheck",    [AuthController::class, "helpCheck"])->name("auth.helpCheck");
Route::post("/login",       [AuthController::class, "loginApi"])->name("api.auth.login");
Route::post("/logout",      [AuthController::class, "logoutApi"])->name("api.auth.logout");


?>
