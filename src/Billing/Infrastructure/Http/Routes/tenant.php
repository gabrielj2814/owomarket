<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Src\Billing\Infrastructure\Http\Controller\ViewBillingIndexGETController;
use Src\Billing\Infrastructure\Http\Controller\ViewBillingSettingsGETController;
use Src\Billing\Infrastructure\Http\Controller\ViewInvoiceDetailGETController;

Route::get('/backoffice/{user_uuid}/module', [ViewBillingIndexGETController::class, 'index'])
    ->name('tenant.backoffice.web.billing.module')
    ->middleware('auth');

Route::get('/backoffice/{user_uuid}/settings', [ViewBillingSettingsGETController::class, 'index'])
    ->name('tenant.backoffice.web.billing.settings')
    ->middleware('auth');

Route::get('/backoffice/{user_uuid}/invoice/{id}', [ViewInvoiceDetailGETController::class, 'index'])
    ->name('tenant.backoffice.web.billing.invoice.detail')
    ->middleware('auth');
