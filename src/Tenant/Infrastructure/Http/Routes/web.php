<?php

use Illuminate\Support\Facades\Route;
use Src\Tenant\Infrastructure\Http\Controller\FiltrarTenantsPOSTController;
use Src\Tenant\Infrastructure\Http\Controller\ViewModuleTenantIndexGETController;

Route::get('/{user_uuid}/module',                       [ViewModuleTenantIndexGETController::class, 'index'])->name('central.backoffice.web.admin.module.tenant.index')->middleware("auth");
Route::post('filter',                                   [FiltrarTenantsPOSTController::class, 'index'])->middleware("auth");

?>
