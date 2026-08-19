<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Src\TenantSettings\Infrastructure\Http\Controller\DeleteSettingDELETEController;
use Src\TenantSettings\Infrastructure\Http\Controller\GetSettingByKeyGETController;
use Src\TenantSettings\Infrastructure\Http\Controller\GetStoreSettingsGETController;
use Src\TenantSettings\Infrastructure\Http\Controller\ListSettingsByGroupGETController;
use Src\TenantSettings\Infrastructure\Http\Controller\SaveSettingPOSTController;
use Src\TenantSettings\Infrastructure\Http\Controller\UpdateStoreSettingsPUTController;

Route::get('/', GetStoreSettingsGETController::class);
Route::put('/', UpdateStoreSettingsPUTController::class);
Route::get('group/{group}', ListSettingsByGroupGETController::class);
Route::get('item/{key}', GetSettingByKeyGETController::class);
Route::post('item', SaveSettingPOSTController::class);
Route::delete('item/{key}', DeleteSettingDELETEController::class);
