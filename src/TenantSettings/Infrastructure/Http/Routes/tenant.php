<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Src\TenantSettings\Infrastructure\Http\Controller\ViewTenantSettingsIndexGETController;

Route::get('/backoffice/{user_uuid}/module', [ViewTenantSettingsIndexGETController::class, 'index'])->name('tenant.settings.index');
