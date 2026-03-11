<?php

namespace Src\Product\Infrastructure\Http\Routes;

use Illuminate\Support\Facades\Route;
use Src\Product\Infrastructure\Http\Controller\CreateProductPOSTController;

Route::post('/create', [CreateProductPOSTController::class, 'index']);


?>
