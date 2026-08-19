<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Src\Monetization\Infrastructure\Http\Controller\GetTenantMonetizationSummaryGETController;
use Src\Monetization\Infrastructure\Http\Controller\ListPlansGETController;
use Src\Monetization\Infrastructure\Http\Controller\SubscribeTenantPOSTController;

Route::get('/summary', GetTenantMonetizationSummaryGETController::class)->name('tenant.monetization.summary');
Route::get('/plans', ListPlansGETController::class)->name('tenant.monetization.plans');
Route::post('/subscribe', SubscribeTenantPOSTController::class)->name('tenant.monetization.subscribe');
