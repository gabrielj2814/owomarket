<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Src\ExchangeRate\Infrastructure\Http\Controller\AdminCreateManualRatePOSTController;
use Src\ExchangeRate\Infrastructure\Http\Controller\AdminListRatesHistoryGETController;
use Src\ExchangeRate\Infrastructure\Http\Controller\AdminSyncBcvPOSTController;
use Src\ExchangeRate\Infrastructure\Http\Controller\AdminViewExchangeRateGETController;

Route::middleware('auth')->group(function () {
    Route::get('/backoffice/{user_uuid}/exchange-rates', AdminViewExchangeRateGETController::class)
        ->name('central.backoffice.web.admin.exchange_rate.index');

    Route::post('/backoffice/exchange-rates/sync-bcv', AdminSyncBcvPOSTController::class)
        ->name('central.backoffice.web.admin.exchange_rate.sync');

    Route::post('/backoffice/exchange-rates/manual', AdminCreateManualRatePOSTController::class)
        ->name('central.backoffice.web.admin.exchange_rate.manual');

    Route::get('/backoffice/exchange-rates/history', AdminListRatesHistoryGETController::class)
        ->name('central.backoffice.web.admin.exchange_rate.history');
});
