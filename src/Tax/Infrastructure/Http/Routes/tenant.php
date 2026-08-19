<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Src\Tax\Infrastructure\Http\Controller\ViewTaxIndexGETController;

Route::get('/backoffice/{user_uuid}/module', [ViewTaxIndexGETController::class, 'index'])
    ->name('tenant.backoffice.web.tax.module')
    ->middleware('auth');
