<?php

declare(strict_types=1);

namespace Src\Category\Infrastructure\Http\Routes;

use Illuminate\Support\Facades\Route;
use Src\Category\Infrastructure\Http\Controller\ConsultCategoryGETController;
use Src\Category\Infrastructure\Http\Controller\CreateCategoryPOSTController;
use Src\Category\Infrastructure\Http\Controller\DeleteCategoryDELETEController;
use Src\Category\Infrastructure\Http\Controller\EditCategoryPUTController;
use Src\Category\Infrastructure\Http\Controller\FilterCategoriesPOSTController;
use Src\Category\Infrastructure\Http\Controller\ListCategoriesTreeGETController;

Route::post('/filter', FilterCategoriesPOSTController::class);
Route::get('/tree', ListCategoriesTreeGETController::class);
Route::post('/create', CreateCategoryPOSTController::class);
Route::get('/{id}', ConsultCategoryGETController::class)->whereNumber('id');
Route::put('/{id}', EditCategoryPUTController::class)->whereNumber('id');
Route::delete('/{id}', DeleteCategoryDELETEController::class)->whereNumber('id');
