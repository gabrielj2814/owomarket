<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Src\Payment\Infrastructure\Http\Controller\ListPaymentGatewaysGETController;
use Src\Payment\Infrastructure\Http\Controller\ProcessPaymentPOSTController;

// Payment Gateways & Processing Endpoints
Route::get('/gateways', ListPaymentGatewaysGETController::class);
Route::post('/process', ProcessPaymentPOSTController::class);
