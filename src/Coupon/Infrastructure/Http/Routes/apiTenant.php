<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Src\Coupon\Infrastructure\Http\Controller\ConsultCouponGETController;
use Src\Coupon\Infrastructure\Http\Controller\CreateCouponPOSTController;
use Src\Coupon\Infrastructure\Http\Controller\DeleteCouponDELETEController;
use Src\Coupon\Infrastructure\Http\Controller\EditCouponPUTController;
use Src\Coupon\Infrastructure\Http\Controller\FilterCouponsPOSTController;
use Src\Coupon\Infrastructure\Http\Controller\ValidateCouponPOSTController;

Route::post('/create', CreateCouponPOSTController::class);
Route::post('/filter', FilterCouponsPOSTController::class);
Route::post('/validate', ValidateCouponPOSTController::class);
Route::get('/{id}', ConsultCouponGETController::class);
Route::put('/{id}', EditCouponPUTController::class);
Route::delete('/{id}', DeleteCouponDELETEController::class);
