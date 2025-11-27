<?php

use Illuminate\Support\Facades\Route;
use Src\Shared\Infrastructure\Http\Middleware\InternalServiceMiddleware;
use Src\User\Infrastructure\Http\Controller\ConsultUserByEmailPOSTController;


Route::prefix("public")->group(function () {


});


Route::middleware(InternalServiceMiddleware::class)->prefix("interna")->group(function () {

    Route::post("/consulta-usuario-por-email",       [ConsultUserByEmailPOSTController::class, "index"])->name("interna.api.user.consultUserByEmail");

});



?>
