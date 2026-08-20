<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Src\SupportTicket\Infrastructure\Http\Controller\AddSupportTicketMessagePOSTController;
use Src\SupportTicket\Infrastructure\Http\Controller\CreateSupportTicketPOSTController;
use Src\SupportTicket\Infrastructure\Http\Controller\GetSupportTicketDetailGETController;
use Src\SupportTicket\Infrastructure\Http\Controller\ListSupportTicketsGETController;
use Src\SupportTicket\Infrastructure\Http\Controller\UpdateSupportTicketStatusPATCHController;
use Src\SupportTicket\Infrastructure\Http\Controller\ViewCustomerSupportGETController;
use Src\SupportTicket\Infrastructure\Http\Controller\ViewTenantOwnerSupportGETController;

// Vistas Web
Route::get('/owner/backoffice/{user_uuid}/support', ViewTenantOwnerSupportGETController::class)
    ->name('central.backoffice.web.tenant.owner.support')
    ->middleware('auth');

Route::get('/account/support', ViewCustomerSupportGETController::class)
    ->name('central.customer.support');

// Endpoints API
Route::prefix('api/support')->group(function () {
    Route::get('/tickets', ListSupportTicketsGETController::class);
    Route::post('/tickets', CreateSupportTicketPOSTController::class);
    Route::get('/tickets/{id}', GetSupportTicketDetailGETController::class);
    Route::post('/tickets/{id}/messages', AddSupportTicketMessagePOSTController::class);
    Route::patch('/tickets/{id}/status', UpdateSupportTicketStatusPATCHController::class);
});
