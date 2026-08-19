<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Src\Review\Infrastructure\Http\Controller\ConsultReviewGETController;
use Src\Review\Infrastructure\Http\Controller\CreateProductReviewPOSTController;
use Src\Review\Infrastructure\Http\Controller\DeleteProductReviewDELETEController;
use Src\Review\Infrastructure\Http\Controller\FilterReviewsPOSTController;
use Src\Review\Infrastructure\Http\Controller\GetProductRatingSummaryGETController;
use Src\Review\Infrastructure\Http\Controller\ModerateReviewPOSTController;
use Src\Review\Infrastructure\Http\Controller\RespondReviewPOSTController;
use Src\Review\Infrastructure\Http\Controller\UpdateProductReviewPUTController;

Route::post('filter', FilterReviewsPOSTController::class);
Route::get('summary/{productId?}', GetProductRatingSummaryGETController::class);
Route::post('create', CreateProductReviewPOSTController::class);
Route::get('{id}', ConsultReviewGETController::class);
Route::post('{id}/moderate', ModerateReviewPOSTController::class);
Route::post('{id}/respond', RespondReviewPOSTController::class);
Route::put('{id}', UpdateProductReviewPUTController::class);
Route::delete('{id}', DeleteProductReviewDELETEController::class);
