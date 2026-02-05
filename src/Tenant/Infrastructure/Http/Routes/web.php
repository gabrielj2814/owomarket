<?php

use Illuminate\Support\Facades\Route;
use Src\Tenant\Infrastructure\Http\Controller\ActiveTenantByUuidPATCHController;
use Src\Tenant\Infrastructure\Http\Controller\ApprovedTenantByUuidPATCHController;
use Src\Tenant\Infrastructure\Http\Controller\ConsultTenantByUuidGETController;
use Src\Tenant\Infrastructure\Http\Controller\ConsultTenantByUuidOfOwnerPOSTController;
use Src\Tenant\Infrastructure\Http\Controller\CreateAccountTenantPOSTController;
use Src\Tenant\Infrastructure\Http\Controller\CreateTenantPOSTController;
use Src\Tenant\Infrastructure\Http\Controller\DeleteTenantDELETEController;
use Src\Tenant\Infrastructure\Http\Controller\FiltrarTenantsPOSTController;
use Src\Tenant\Infrastructure\Http\Controller\InactiveTenantByUuidPATCHController;
use Src\Tenant\Infrastructure\Http\Controller\RejectedTenantByUuidPATCHController;
use Src\Tenant\Infrastructure\Http\Controller\SuspendedTenantByUuidPATCHController;
use Src\Tenant\Infrastructure\Http\Controller\ViewCreateAccountTenantGETController;
use Src\Tenant\Infrastructure\Http\Controller\ViewDashboardCentralTenantOwnerIndexGETController;
use Src\Tenant\Infrastructure\Http\Controller\ViewModuleTenantIndexGETController;
use Src\Tenant\Infrastructure\Http\Controller\ViewModuleTenantRequestIndexGETController;
use Src\Tenant\Infrastructure\Http\Controller\ViewModuleTenantSuspendedIndexGETController;

// module tenant
Route::get('/backoffice/{user_uuid}/module',                       [ViewModuleTenantIndexGETController::class, 'index'])->name('central.backoffice.web.admin.module.tenant.index')->middleware("auth");
Route::post('/backoffice/filter',                                  [FiltrarTenantsPOSTController::class, 'index'])->middleware("auth");
Route::get('/backoffice/{id}',                                     [ConsultTenantByUuidGETController::class, 'index'])->middleware("auth");
Route::patch('/backoffice/{id}/suspended',                         [SuspendedTenantByUuidPATCHController::class, 'index'])->middleware("auth");
Route::patch('/backoffice/{id}/active',                            [ActiveTenantByUuidPATCHController::class, 'index'])->middleware("auth");
Route::patch('/backoffice/{id}/inactive',                          [InactiveTenantByUuidPATCHController::class, 'index'])->middleware("auth");

// module tenant suspended/inactive
Route::get('/backoffice/{user_uuid}/module/suspended',             [ViewModuleTenantSuspendedIndexGETController::class, 'index'])->name('central.backoffice.web.admin.module.tenant.suspended')->middleware("auth");

// module tenant request
Route::get('/backoffice/{user_uuid}/module/request',               [ViewModuleTenantRequestIndexGETController::class, 'index'])->name('central.backoffice.web.admin.module.tenant.request')->middleware("auth");
Route::patch('/backoffice/{id}/rejected',                          [RejectedTenantByUuidPATCHController::class, 'index'])->middleware("auth");
Route::patch('/backoffice/{id}/approved',                          [ApprovedTenantByUuidPATCHController::class, 'index'])->middleware("auth");

Route::get('/create/account',                                      [ViewCreateAccountTenantGETController::class, 'index'])->name('central.web.signup.create.account.tenant');
Route::post('/create/account',                                     [CreateAccountTenantPOSTController::class, 'index']);


// Rutas del Tenant Owner Central
Route::get('/owner/backoffice/{user_uuid}/dashboard',              [ViewDashboardCentralTenantOwnerIndexGETController::class, 'index'])->name('central.backoffice.web.tenant.owner.dashboard')->middleware("auth");

// Rutas del Tenant Staff Central
// Route::get('/staff/backoffice/{user_uuid}/dashboard',                       [ViewModuleTenantIndexGETController::class, 'index'])->name('central.backoffice.web.admin.module.tenant.index')->middleware("auth");

// Route::put('/owner/update/personal-data/{id}',                     [TenantOwnerUpdatePersonalDataPUTController::class, 'index']);
// Route::put('/owner/update/password/{id}',                          [TenantOwnerUpdatePasswordPUTController::class, 'index']);
// Route::delete('/owner/cancel-account/{id}',                        [CancelAccountTenantOwnerDELETEController::class, 'index']);
Route::post('/owner/filter/tenants',                               [ConsultTenantByUuidOfOwnerPOSTController::class, 'index'])->middleware("auth");
Route::post('/owner/tenant',                                       [CreateTenantPOSTController::class, 'index'])->middleware("auth");
Route::delete('/owner/tenant',                                     [DeleteTenantDELETEController::class, 'index'])->middleware("auth");


?>
