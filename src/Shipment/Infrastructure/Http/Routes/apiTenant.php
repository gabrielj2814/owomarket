<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Src\Shipment\Infrastructure\Http\Controller\ConsultShipmentGETController;
use Src\Shipment\Infrastructure\Http\Controller\ConsultShipmentsByOrderGETController;
use Src\Shipment\Infrastructure\Http\Controller\CreateShipmentPOSTController;
use Src\Shipment\Infrastructure\Http\Controller\FilterShipmentsPOSTController;
use Src\Shipment\Infrastructure\Http\Controller\GetShipmentMetricsGETController;
use Src\Shipment\Infrastructure\Http\Controller\MarkShipmentAsDeliveredPOSTController;
use Src\Shipment\Infrastructure\Http\Controller\UpdateShipmentTrackingPOSTController;

Route::get('/metrics', GetShipmentMetricsGETController::class);
Route::post('/filter', FilterShipmentsPOSTController::class);
Route::post('/create', CreateShipmentPOSTController::class);
Route::get('/order/{orderId}', ConsultShipmentsByOrderGETController::class);
Route::get('/{id}', ConsultShipmentGETController::class);
Route::post('/{id}/tracking', UpdateShipmentTrackingPOSTController::class);
Route::post('/{id}/deliver', MarkShipmentAsDeliveredPOSTController::class);
