<?php

namespace Src\Product\Infrastructure\Http\Routes;

use Illuminate\Support\Facades\Route;
use Src\Product\Infrastructure\Http\Controller\CreateProductPOSTController;
use Src\Product\Infrastructure\Http\Controller\ConsultProductByUuidGETController;
use Src\Product\Infrastructure\Http\Controller\DeleteProductByUuidDELETEController;

Route::post('/create', [CreateProductPOSTController::class, 'index']);
Route::get('/{uuid}', [ConsultProductByUuidGETController::class, 'index']);
Route::delete('/{uuid}', [DeleteProductByUuidDELETEController::class, 'index']);
// Route::put('/{uuid}', [CreateProductPOSTController::class, 'index']);
// Route::post('/filtrar', [CreateProductPOSTController::class, 'index']);


?>
