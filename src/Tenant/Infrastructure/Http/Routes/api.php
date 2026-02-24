<?php

use Illuminate\Support\Facades\Route;
use Src\Shared\Infrastructure\Http\Middleware\InternalServiceMiddleware;
use Src\Tenant\Infrastructure\Http\Controller\ConsultTenantLoginIsActiveGETController;


Route::prefix("public")->group(function () {
    // Route::post('filtrar',                                             [FiltrarTenantsPOSTController::class, 'index']);
    // Route::post('/create/account',                                     [CreateAccountTenantPOSTController::class, 'index']);
    // Route::put('/owner/update/personal-data/{id}',                     [TenantOwnerUpdatePersonalDataPUTController::class, 'index']);
    // Route::put('/owner/update/password/{id}',                          [TenantOwnerUpdatePasswordPUTController::class, 'index']);
    // Route::delete('/owner/cancel-account/{id}',                        [CancelAccountTenantOwnerDELETEController::class, 'index']);

});



Route::middleware(InternalServiceMiddleware::class)->prefix("interna")->group(function () {

    Route::get('/consult/login-is-active/{slug}', [ConsultTenantLoginIsActiveGETController::class, 'index']);

});

?>
