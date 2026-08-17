<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Src\Tax\Infrastructure\Http\Controller\CalculateTaxPOSTController;
use Src\Tax\Infrastructure\Http\Controller\ConsultTaxRateGETController;
use Src\Tax\Infrastructure\Http\Controller\CreateTaxRatePOSTController;
use Src\Tax\Infrastructure\Http\Controller\DeleteTaxRateDELETEController;
use Src\Tax\Infrastructure\Http\Controller\EditTaxRatePUTController;
use Src\Tax\Infrastructure\Http\Controller\FilterTaxRatesPOSTController;

Route::post('/create', CreateTaxRatePOSTController::class);
Route::post('/filter', FilterTaxRatesPOSTController::class);
Route::post('/calculate', CalculateTaxPOSTController::class);
Route::get('/{id}', ConsultTaxRateGETController::class);
Route::put('/{id}', EditTaxRatePUTController::class);
Route::delete('/{id}', DeleteTaxRateDELETEController::class);
