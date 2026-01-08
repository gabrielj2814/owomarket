<?php

use Illuminate\Support\Facades\Route;
use Src\Tenant\Infrastructure\Http\Controller\CreateAccountTenantPOSTController;
use Src\Tenant\Infrastructure\Http\Controller\FiltrarTenantsPOSTController;

// Route::post('filtrar',                                   [FiltrarTenantsPOSTController::class, 'index']);
Route::post('/create/account',                                     [CreateAccountTenantPOSTController::class, 'index']);

?>
