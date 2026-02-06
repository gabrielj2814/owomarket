<?php

use Illuminate\Support\Facades\Route;
use Src\Tenant\Infrastructure\Http\Controller\ViewDashboardTenantGETController;

Route::get('/backoffice/{user_uuid}/dashboard',                               [ViewDashboardTenantGETController::class, 'index'])->middleware("auth");
Route::get('/test', function () {
    return "Hola desde tenant dashboard";
});


?>
