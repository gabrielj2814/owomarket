<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Src\Product\Infrastructure\Http\Controller\ConsultProductGETController;
use Src\Product\Infrastructure\Http\Controller\CreateProductPOSTController;
use Src\Product\Infrastructure\Http\Controller\DeleteProductDELETEController;
use Src\Product\Infrastructure\Http\Controller\EditProductPUTController;
use Src\Product\Infrastructure\Http\Controller\FilterProductsPOSTController;
use Src\Product\Infrastructure\Http\Controller\ToggleProductVisibilityPATCHController;
use Src\Product\Infrastructure\Http\Controller\UpdateProductStockPATCHController;

Route::post('/create', CreateProductPOSTController::class);
Route::post('/filter', FilterProductsPOSTController::class);
Route::get('/{id}', ConsultProductGETController::class);
Route::put('/{id}', EditProductPUTController::class);
Route::delete('/{id}', DeleteProductDELETEController::class);
Route::patch('/{id}/toggle-visibility', ToggleProductVisibilityPATCHController::class);
Route::patch('/{id}/stock', UpdateProductStockPATCHController::class);
