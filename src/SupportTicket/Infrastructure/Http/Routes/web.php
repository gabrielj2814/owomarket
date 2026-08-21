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

/*
|--------------------------------------------------------------------------
| Endpoints API — Mesa de soporte central (público-autenticado)
|--------------------------------------------------------------------------
|
| Este archivo se monta DOS veces desde routes/web.php: sin prefijo (para
| clientes, vía /account/support) y bajo /tenant (para el propietario de
| tienda, vía /tenant/owner/backoffice/{uuid}/support). Ambos comparten los
| mismos controladores.
|
| Hasta la Fase 0.3-C este grupo no tenía NINGÚN middleware, y los
| controladores tomaban 'user_id'/'sender_type' del cuerpo de la petición
| (hallazgo A6). El middleware 'support_session' exige que exista una sesión
| real (propietario o cliente); los controladores resuelven la identidad
| SIEMPRE desde esa sesión con Src\SupportTicket\Infrastructure\Http\Support\
| ResolvesSupportRequester, nunca desde el request.
|
*/
Route::middleware('support_session')->prefix('api/support')->group(function () {
    Route::get('/tickets', ListSupportTicketsGETController::class);
    Route::post('/tickets', CreateSupportTicketPOSTController::class);
    Route::get('/tickets/{id}', GetSupportTicketDetailGETController::class);
    Route::post('/tickets/{id}/messages', AddSupportTicketMessagePOSTController::class);
    Route::patch('/tickets/{id}/status', UpdateSupportTicketStatusPATCHController::class);
});
