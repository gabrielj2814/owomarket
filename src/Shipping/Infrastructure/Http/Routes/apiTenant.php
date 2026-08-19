<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Src\Shipping\Infrastructure\Http\Controller\CalculateShippingPOSTController;
use Src\Shipping\Infrastructure\Http\Controller\ConsultShippingZoneGETController;
use Src\Shipping\Infrastructure\Http\Controller\CreateShippingRatePOSTController;
use Src\Shipping\Infrastructure\Http\Controller\CreateShippingZonePOSTController;
use Src\Shipping\Infrastructure\Http\Controller\DeleteShippingRateDELETEController;
use Src\Shipping\Infrastructure\Http\Controller\DeleteShippingZoneDELETEController;
use Src\Shipping\Infrastructure\Http\Controller\EditShippingZonePUTController;
use Src\Shipping\Infrastructure\Http\Controller\FilterShippingZonesPOSTController;

Route::post('/zones/create', CreateShippingZonePOSTController::class);
Route::post('/zones/filter', FilterShippingZonesPOSTController::class);
Route::get('/zones/{id}', ConsultShippingZoneGETController::class);
Route::put('/zones/{id}', EditShippingZonePUTController::class);
Route::delete('/zones/{id}', DeleteShippingZoneDELETEController::class);

Route::post('/zones/{shippingZoneId}/rates', CreateShippingRatePOSTController::class);
Route::delete('/rates/{id}', DeleteShippingRateDELETEController::class);

Route::post('/calculate', CalculateShippingPOSTController::class);
