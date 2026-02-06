<?php

use Illuminate\Support\Facades\Route;
use Src\Authentication\Infrastructure\Http\Controller\CurrentUserGETController;
use Src\Shared\Infrastructure\Http\Middleware\InternalServiceMiddleware;


Route::middleware(InternalServiceMiddleware::class)->prefix("interna")->group(function () {

    Route::get("/user/{user_uuid}",         [CurrentUserGETController::class, "index"])->name("tenant.api.auth.current.user");

});


?>
