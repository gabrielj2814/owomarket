<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Src\Coupon\Infrastructure\Http\Controller\ViewCouponIndexGETController;

Route::get('/backoffice/{user_uuid}/module', [ViewCouponIndexGETController::class, 'index'])
    ->name('tenant.backoffice.web.coupon.module')
    ->middleware('auth');
