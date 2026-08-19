<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Src\Brand\Infrastructure\Http\Controller\ConsultBrandGETController;
use Src\Brand\Infrastructure\Http\Controller\CreateBrandPOSTController;
use Src\Brand\Infrastructure\Http\Controller\DeleteBrandDELETEController;
use Src\Brand\Infrastructure\Http\Controller\EditBrandPUTController;
use Src\Brand\Infrastructure\Http\Controller\FilterBrandsPOSTController;
use Src\Brand\Infrastructure\Http\Controller\ListAllActiveBrandsGETController;
use Src\Brand\Infrastructure\Http\Controller\SyncCentralBrandsPOSTController;

Route::post('/sync-central', SyncCentralBrandsPOSTController::class);
Route::post('/create', CreateBrandPOSTController::class);
Route::post('/filter', FilterBrandsPOSTController::class);
Route::get('/active', ListAllActiveBrandsGETController::class);
Route::get('/{id}', ConsultBrandGETController::class);
Route::put('/{id}', EditBrandPUTController::class);
Route::delete('/{id}', DeleteBrandDELETEController::class);
