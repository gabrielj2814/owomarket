<?php

use App\Modules\Core\Auth\Controller\AuthController;
use Illuminate\Support\Facades\Route;

Route::post("/login",     [AuthController::class, "login"])->name("web.auth.login");
Route::post("/logout",    [AuthController::class, "login"])->name("web.auth.logout");

?>
