<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Src\Billing\Infrastructure\Http\Controller\CancelInvoicePOSTController;
use Src\Billing\Infrastructure\Http\Controller\ConsultBillingProfileGETController;
use Src\Billing\Infrastructure\Http\Controller\ConsultInvoiceGETController;
use Src\Billing\Infrastructure\Http\Controller\CreateDirectInvoicePOSTController;
use Src\Billing\Infrastructure\Http\Controller\DownloadInvoicePdfGETController;
use Src\Billing\Infrastructure\Http\Controller\FilterInvoicesPOSTController;
use Src\Billing\Infrastructure\Http\Controller\GetBillingMetricsGETController;
use Src\Billing\Infrastructure\Http\Controller\ResendInvoiceMailPOSTController;
use Src\Billing\Infrastructure\Http\Controller\UpdateBillingProfilePUTController;

// Billing Profile Endpoints
Route::get('/profile', ConsultBillingProfileGETController::class);
Route::put('/profile', UpdateBillingProfilePUTController::class);

// Invoices & Metrics Endpoints
Route::get('/metrics', GetBillingMetricsGETController::class);
Route::post('/invoices', CreateDirectInvoicePOSTController::class);
Route::post('/invoices/filter', FilterInvoicesPOSTController::class);
Route::get('/invoices/{id}', ConsultInvoiceGETController::class);
Route::get('/invoices/{id}/pdf', DownloadInvoicePdfGETController::class);
Route::post('/invoices/{id}/cancel', CancelInvoicePOSTController::class);
Route::post('/invoices/{id}/resend-email', ResendInvoiceMailPOSTController::class);
