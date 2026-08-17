<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Src\Category\Infrastructure\Http\Controller\ViewCategoryIndexGETController;

Route::get('/backoffice/{user_uuid}/module', [ViewCategoryIndexGETController::class, 'index'])
    ->name('tenant.backoffice.web.category.module')
    ->middleware('auth');
