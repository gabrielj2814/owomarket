<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Src\CentralMarketplace\Infrastructure\Http\Controller\CreateUnifiedCentralOrderPOSTController;
use Src\CentralMarketplace\Infrastructure\Http\Controller\GetCentralOrderConfirmationGETController;

Route::post('/checkout/create-order', CreateUnifiedCentralOrderPOSTController::class);
Route::get('/order/{id}/confirmation', GetCentralOrderConfirmationGETController::class);
