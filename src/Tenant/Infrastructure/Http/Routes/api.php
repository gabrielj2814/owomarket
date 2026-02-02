<?php

use Illuminate\Support\Facades\Route;
use Src\Tenant\Infrastructure\Http\Controller\CancelAccountTenantOwnerDELETEController;
use Src\Tenant\Infrastructure\Http\Controller\ConsultTenantByUuidOfOwnerPOSTController;
use Src\Tenant\Infrastructure\Http\Controller\CreateAccountTenantPOSTController;
use Src\Tenant\Infrastructure\Http\Controller\CreateTenantController;
use Src\Tenant\Infrastructure\Http\Controller\DeleteTenantController;
use Src\Tenant\Infrastructure\Http\Controller\FiltrarTenantsPOSTController;
use Src\Tenant\Infrastructure\Http\Controller\TenantOwnerUpdatePasswordPUTController;
use Src\Tenant\Infrastructure\Http\Controller\TenantOwnerUpdatePersonalDataPUTController;

// Route::post('filtrar',                                   [FiltrarTenantsPOSTController::class, 'index']);
Route::post('/create/account',                                     [CreateAccountTenantPOSTController::class, 'index']);
Route::put('/owner/update/personal-data/{id}',                     [TenantOwnerUpdatePersonalDataPUTController::class, 'index']);
Route::put('/owner/update/password/{id}',                          [TenantOwnerUpdatePasswordPUTController::class, 'index']);
Route::delete('/owner/cancel-account/{id}',                        [CancelAccountTenantOwnerDELETEController::class, 'index']);
Route::post('/owner/filter/tenants',                               [ConsultTenantByUuidOfOwnerPOSTController::class, 'index']);
Route::post('/owner/tenant',                                       [CreateTenantController::class, 'index']);
Route::delete('/owner/tenant',                                     [DeleteTenantController::class, 'index']);


?>
