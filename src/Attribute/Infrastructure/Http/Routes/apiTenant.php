<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Src\Attribute\Infrastructure\Http\Controller\ConsultAttributeGETController;
use Src\Attribute\Infrastructure\Http\Controller\CreateAttributePOSTController;
use Src\Attribute\Infrastructure\Http\Controller\CreateAttributeValuePOSTController;
use Src\Attribute\Infrastructure\Http\Controller\DeleteAttributeDELETEController;
use Src\Attribute\Infrastructure\Http\Controller\DeleteAttributeValueDELETEController;
use Src\Attribute\Infrastructure\Http\Controller\EditAttributePUTController;
use Src\Attribute\Infrastructure\Http\Controller\FilterAttributesPOSTController;
use Src\Attribute\Infrastructure\Http\Controller\ListAttributesWithValuesGETController;

Route::post('/create', CreateAttributePOSTController::class);
Route::post('/filter', FilterAttributesPOSTController::class);
Route::get('/with-values', ListAttributesWithValuesGETController::class);
Route::get('/{id}', ConsultAttributeGETController::class);
Route::put('/{id}', EditAttributePUTController::class);
Route::delete('/{id}', DeleteAttributeDELETEController::class);

Route::post('/{attributeId}/values', CreateAttributeValuePOSTController::class);
Route::delete('/values/{valueId}', DeleteAttributeValueDELETEController::class);
