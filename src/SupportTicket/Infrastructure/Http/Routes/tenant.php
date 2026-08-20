<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Src\SupportTicket\Infrastructure\Http\Controller\AddTenantStoreSupportMessagePOSTController;
use Src\SupportTicket\Infrastructure\Http\Controller\CreateTenantStoreSupportTicketPOSTController;
use Src\SupportTicket\Infrastructure\Http\Controller\GetSupportTicketDetailGETController;
use Src\SupportTicket\Infrastructure\Http\Controller\ListTenantStoreSupportTicketsGETController;
use Src\SupportTicket\Infrastructure\Http\Controller\UpdateSupportTicketStatusPATCHController;
use Src\SupportTicket\Infrastructure\Http\Controller\ViewTenantStoreSupportGETController;

// Vista del Módulo de Soporte en el Backoffice del Inquilino
Route::get('/backoffice/{user_uuid}/module', ViewTenantStoreSupportGETController::class)
    ->name('tenant.backoffice.web.support.module')
    ->middleware('auth');

// Endpoints API en el contexto del Inquilino
Route::prefix('api')->middleware('auth')->group(function () {
    Route::get('/tickets', ListTenantStoreSupportTicketsGETController::class);
    Route::post('/tickets', CreateTenantStoreSupportTicketPOSTController::class);
    Route::get('/tickets/{id}', GetSupportTicketDetailGETController::class);
    Route::post('/tickets/{id}/messages', AddTenantStoreSupportMessagePOSTController::class);
    Route::patch('/tickets/{id}/status', UpdateSupportTicketStatusPATCHController::class);
});
