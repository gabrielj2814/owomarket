<?php

use Illuminate\Support\Facades\Route;
use Src\Tenant\Infrastructure\Http\Controller\CreateAccountTenantPOSTController;
use Src\Tenant\Infrastructure\Http\Controller\FiltrarTenantsPOSTController;
use Src\Tenant\Infrastructure\Http\Controller\TenantUpdatePersonalDataPUTController;

// Route::post('filtrar',                                   [FiltrarTenantsPOSTController::class, 'index']);
Route::post('/create/account',                                     [CreateAccountTenantPOSTController::class, 'index']);
Route::put('/owner/update/personal-data/{id}',                     [TenantUpdatePersonalDataPUTController::class, 'index']);


?>
