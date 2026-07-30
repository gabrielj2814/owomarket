<?php

use Illuminate\Support\Facades\Route;
use Src\Admin\Infrastructure\Http\Controller\ChangeStatuAdminByUuidPATCHController;
use Src\Admin\Infrastructure\Http\Controller\ConsultAdminByUuidGETController;
use Src\Admin\Infrastructure\Http\Controller\CreateAdminPOSTController;
use Src\Admin\Infrastructure\Http\Controller\DeleteAdminByUuidDELETEController;
use Src\Admin\Infrastructure\Http\Controller\FilterAdminsPOSTController;
use Src\Admin\Infrastructure\Http\Controller\UpdateAdminPUTController;
use Src\Admin\Infrastructure\Http\Controller\ViewDashboardAdminGETController;
use Src\Admin\Infrastructure\Http\Controller\ViewModuleAdminIndexGETController;
use Src\Admin\Infrastructure\Http\Controller\ViewModuloAdminFormGETController;
use Src\Admin\Infrastructure\Http\Controller\ViewAdminProfileGETController;
use Src\Admin\Infrastructure\Http\Controller\UpdateAdminProfilePUTController;
use Src\Admin\Infrastructure\Http\Controller\UploadAdminAvatarPOSTController;
use Src\Admin\Infrastructure\Http\Controller\SendSecurityPinPOSTController;
use Src\Admin\Infrastructure\Http\Controller\ChangePasswordWithPinPUTController;

Route::get('/backoffice/{user_uuid}/dashboard',                          [ViewDashboardAdminGETController::class, 'index'])->name('central.backoffice.web.admin.dashboard')->middleware("auth");
Route::get('/backoffice/{user_uuid}/module',                             [ViewModuleAdminIndexGETController::class, 'index'])->name('central.backoffice.web.admin.module.admin')->middleware("auth");
Route::get('/backoffice/{user_uuid}/module/record/{record_id?}',         [ViewModuloAdminFormGETController::class, 'index'])->name('central.backoffice.web.admin.module.admin.form')->middleware("auth");

// Rutas de Perfil Administrativo
Route::get('/backoffice/{user_uuid}/profile',                            [ViewAdminProfileGETController::class, 'index'])->name('central.backoffice.web.admin.profile')->middleware("auth");
Route::put('/backoffice/{user_uuid}/profile',                            [UpdateAdminProfilePUTController::class, 'index'])->middleware("auth");
Route::post('/backoffice/{user_uuid}/profile/avatar',                    [UploadAdminAvatarPOSTController::class, 'index'])->middleware("auth");
Route::post('/backoffice/{user_uuid}/profile/send-pin',                  [SendSecurityPinPOSTController::class, 'index'])->middleware("auth");
Route::put('/backoffice/{user_uuid}/profile/change-password',            [ChangePasswordWithPinPUTController::class, 'index'])->middleware("auth");

Route::post('/backoffice/{user_uuid}/admin',                             [CreateAdminPOSTController::class, 'index'])->middleware("auth");
Route::put('/backoffice/{user_uuid}/admin/{uuid}',                       [UpdateAdminPUTController::class, 'index'])->middleware("auth");
Route::get('/backoffice/{uuid}',                                         [ConsultAdminByUuidGETController::class, 'index'])->middleware("auth");
Route::post('/backoffice/filter',                                        [FilterAdminsPOSTController::class, 'index'])->middleware("auth");
Route::delete('/backoffice/{uuid}',                                      [DeleteAdminByUuidDELETEController::class, 'index'])->middleware("auth");
Route::put('/backoffice/{uuid}/change-statu',                            [ChangeStatuAdminByUuidPATCHController::class, 'index'])->middleware("auth");

?>
