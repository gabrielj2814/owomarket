<?php

use App\Modules\Core\Auth\Controller\AuthController;
use Illuminate\Support\Facades\Route;

Route::get("/helpcheck",  [AuthController::class, "helpCheck"])->name("auth.helpCheck");
Route::post("/login",     [AuthController::class, "login"])->name("auth.login");
Route::post("/logout",    [AuthController::class, "login"])->name("auth.logout");

?>
