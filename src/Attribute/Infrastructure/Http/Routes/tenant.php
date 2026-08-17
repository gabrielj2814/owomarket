<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Src\Attribute\Infrastructure\Http\Controller\ViewAttributeIndexGETController;

Route::get('/backoffice/{user_uuid}/module', [ViewAttributeIndexGETController::class, 'index'])
    ->name('tenant.backoffice.web.attribute.module')
    ->middleware('auth');
