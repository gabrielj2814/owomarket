<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Src\Customer\Infrastructure\Http\Controller\AddCustomerAddressPOSTController;
use Src\Customer\Infrastructure\Http\Controller\ConsultCustomerGETController;
use Src\Customer\Infrastructure\Http\Controller\CreateCustomerPOSTController;
use Src\Customer\Infrastructure\Http\Controller\DeleteCustomerAddressDELETEController;
use Src\Customer\Infrastructure\Http\Controller\DeleteCustomerDELETEController;
use Src\Customer\Infrastructure\Http\Controller\FilterCustomersPOSTController;
use Src\Customer\Infrastructure\Http\Controller\GetCustomerMetricsGETController;
use Src\Customer\Infrastructure\Http\Controller\SetDefaultCustomerAddressPOSTController;
use Src\Customer\Infrastructure\Http\Controller\UpdateCustomerPUTController;

Route::get('/metrics', GetCustomerMetricsGETController::class);
Route::post('/filter', FilterCustomersPOSTController::class);
Route::post('/create', CreateCustomerPOSTController::class);
Route::get('/{id}', ConsultCustomerGETController::class);
Route::put('/{id}', UpdateCustomerPUTController::class);
Route::delete('/{id}', DeleteCustomerDELETEController::class);

// Direcciones del cliente
Route::post('/{id}/address', AddCustomerAddressPOSTController::class);
Route::delete('/{id}/address/{addressId}', DeleteCustomerAddressDELETEController::class);
Route::post('/{id}/address/{addressId}/default', SetDefaultCustomerAddressPOSTController::class);
