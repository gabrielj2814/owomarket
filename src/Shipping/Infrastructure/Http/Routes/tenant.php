<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Src\Shipping\Infrastructure\Http\Controller\ViewShippingIndexGETController;

Route::get('/backoffice/{user_uuid}/module', [ViewShippingIndexGETController::class, 'index'])
    ->name('tenant.backoffice.web.shipping.module')
    ->middleware('auth');
