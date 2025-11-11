<?php

use Illuminate\Support\Facades\Route;
use Src\User\Infrastructure\Http\Controller\ConsultUserByEmailPOSTController;

Route::post("/consulta-usuario-por-email",       [ConsultUserByEmailPOSTController::class, "index"])->name("api.user.consultUserByEmail");


?>
