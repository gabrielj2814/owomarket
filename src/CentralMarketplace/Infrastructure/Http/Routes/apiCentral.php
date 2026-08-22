<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Src\CentralMarketplace\Infrastructure\Http\Controller\CreateUnifiedCentralOrderPOSTController;
use Src\CentralMarketplace\Infrastructure\Http\Controller\GetCentralOrderConfirmationGETController;
use Src\CentralMarketplace\Infrastructure\Http\Controller\RevalidateCentralCartPOSTController;
use Src\Marketplace\Infrastructure\Http\Controller\GetCentralMarketplaceHomeDataAPIController;
use Src\Marketplace\Infrastructure\Http\Controller\GetCentralProductDetailAPIController;
use Src\Marketplace\Infrastructure\Http\Controller\GetCentralProductsAPIController;
use Src\Marketplace\Infrastructure\Http\Controller\GetCentralStoresAPIController;

Route::get('/home-data', GetCentralMarketplaceHomeDataAPIController::class);
Route::get('/products', GetCentralProductsAPIController::class);
Route::get('/product/{slugOrId}', GetCentralProductDetailAPIController::class);
Route::get('/stores', GetCentralStoresAPIController::class);

Route::post('/cart/revalidate', RevalidateCentralCartPOSTController::class);
Route::post('/checkout/create-order', CreateUnifiedCentralOrderPOSTController::class);
Route::get('/order/{id}/confirmation', GetCentralOrderConfirmationGETController::class);
