<?php

use Illuminate\Support\Facades\Route;
use Src\Admin\Infrastructure\Http\Controller\CreateAdminPOSTController;
use Src\Admin\Infrastructure\Http\Controller\ViewDashboardAdminGETController;
use Src\Admin\Infrastructure\Http\Controller\ViewModuleAdminIndexGETController;
use Src\Admin\Infrastructure\Http\Controller\ViewModuloAdminFormGETController;

Route::get('/{user_uuid}/dashboard',                          [ViewDashboardAdminGETController::class, 'index'])->name('central.backoffice.web.admin.dashboard')->middleware("auth");
Route::get('/{user_uuid}/module/admin',                       [ViewModuleAdminIndexGETController::class, 'index'])->name('central.backoffice.web.admin.module.admin')->middleware("auth");
Route::get('/{user_uuid}/module/admin/record/{record_id?}',   [ViewModuloAdminFormGETController::class, 'index'])->name('central.backoffice.web.admin.module.admin.form')->middleware("auth");
Route::post('/{user_uuid}/module/admin/create',               [CreateAdminPOSTController::class, 'index'])->middleware("auth");


?>
