<?php

use Illuminate\Support\Facades\Route;
use Src\Product\Infrastructure\Http\Controller\ViewProductFormGETController;
use Src\Product\Infrastructure\Http\Controller\ViewProductIndexGETController;

Route::get('/backoffice/{user_uuid}/module',        [ViewProductIndexGETController::class, 'index'])->name('tenant.backoffice.web.product.module')->middleware("auth");
Route::get('/backoffice/{user_uuid}/module/record/{record_uuid?}', [ViewProductFormGETController::class, 'index'])->name('tenant.backoffice.web.product.module.form')->middleware("auth");



?>
