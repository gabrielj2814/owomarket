<?php

use Illuminate\Support\Facades\Route;
use Src\Tenant\Infrastructure\Http\Controller\ActiveTenantByUuidPATCHController;
use Src\Tenant\Infrastructure\Http\Controller\ConsultTenantByUuidGETController;
use Src\Tenant\Infrastructure\Http\Controller\FiltrarTenantsPOSTController;
use Src\Tenant\Infrastructure\Http\Controller\InactiveTenantByUuidPATCHController;
use Src\Tenant\Infrastructure\Http\Controller\SuspendedTenantByUuidPATCHController;
use Src\Tenant\Infrastructure\Http\Controller\ViewModuleTenantIndexGETController;
use Src\Tenant\Infrastructure\Http\Controller\ViweModuleTenantSuspendedIndexGETController;

// module tenant
Route::get('/backoffice/{user_uuid}/module',                       [ViewModuleTenantIndexGETController::class, 'index'])->name('central.backoffice.web.admin.module.tenant.index')->middleware("auth");
Route::post('/backoffice/filter',                                  [FiltrarTenantsPOSTController::class, 'index'])->middleware("auth");
Route::get('/backoffice/{id}',                                     [ConsultTenantByUuidGETController::class, 'index'])->middleware("auth");
Route::patch('/backoffice/{id}/suspended',                         [SuspendedTenantByUuidPATCHController::class, 'index'])->middleware("auth");
Route::patch('/backoffice/{id}/active',                            [ActiveTenantByUuidPATCHController::class, 'index'])->middleware("auth");
Route::patch('/{id}/inactive',                                     [InactiveTenantByUuidPATCHController::class, 'index'])->middleware("auth");

// module tenant suspended/inactive
Route::get('/backoffice/{user_uuid}/module/suspended',             [ViweModuleTenantSuspendedIndexGETController::class, 'index'])->name('central.backoffice.web.admin.module.tenant.suspended')->middleware("auth");



?>
