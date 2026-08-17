<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Src\Brand\Infrastructure\Http\Controller\ViewBrandIndexGETController;

Route::get('/backoffice/{user_uuid}/module', [ViewBrandIndexGETController::class, 'index'])
    ->name('tenant.backoffice.web.brand.module')
    ->middleware('auth');
