<?php

use Illuminate\Support\Facades\Route;
use Src\Authentication\Infrastructure\Http\Controller\CurrentUserGETController;
use Src\Authentication\Infrastructure\Http\Controller\LoginApiPOSTController;
use Src\Authentication\Infrastructure\Http\Controller\LogoutApiGETController;
use Src\Shared\Infrastructure\Http\Middleware\InternalServiceMiddleware;

// Route::post("/login",       [LoginApiPOSTController::class, "index"])->name("api.auth.login");
// Route::post("/logout",      [LogoutApiGETController::class, "index"])->name("api.auth.logout")->middleware('auth:sanctum');
// // Route::post("/logout",      [LogoutApiGETController::class, "index"])->name("api.auth.logout");

Route::middleware(InternalServiceMiddleware::class)->prefix("interna")->group(function () {

    Route::get("/user/{user_uuid}",         [CurrentUserGETController::class, "index"])->name("central.api.auth.current.user");

});





?>
