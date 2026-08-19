<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Src\ExchangeRate\Infrastructure\Http\Controller\ConvertCurrencyGETController;
use Src\ExchangeRate\Infrastructure\Http\Controller\GetActiveRateGETController;

Route::get('/current', GetActiveRateGETController::class)->name('api.central.exchange_rate.current');
Route::get('/convert', ConvertCurrencyGETController::class)->name('api.central.exchange_rate.convert');
