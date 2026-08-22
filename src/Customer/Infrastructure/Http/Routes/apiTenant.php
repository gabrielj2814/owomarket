<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Src\CentralCustomer\Infrastructure\Http\Controller\ConsumeSsoTokenPOSTController;
use Src\CentralCustomer\Infrastructure\Http\Controller\CustomerLogoutPOSTController;
use Src\CentralCustomer\Infrastructure\Http\Controller\GetTenantCustomerSessionGETController;
use Src\Customer\Infrastructure\Http\Controller\AddCustomerAddressPOSTController;
use Src\Customer\Infrastructure\Http\Controller\ConsultCustomerGETController;
use Src\Customer\Infrastructure\Http\Controller\CreateCustomerPOSTController;
use Src\Customer\Infrastructure\Http\Controller\DeleteCustomerAddressDELETEController;
use Src\Customer\Infrastructure\Http\Controller\DeleteCustomerDELETEController;
use Src\Customer\Infrastructure\Http\Controller\FilterCustomersPOSTController;
use Src\Customer\Infrastructure\Http\Controller\GetCustomerMetricsGETController;
use Src\Customer\Infrastructure\Http\Controller\SetDefaultCustomerAddressPOSTController;
use Src\Customer\Infrastructure\Http\Controller\UpdateCustomerPUTController;

/*
|--------------------------------------------------------------------------
| PÚBLICAS — SSO y sesión del comprador del storefront
|--------------------------------------------------------------------------
|
| Lista blanca de la Fase 0.3-E. Estas tres NO pueden llevar 'auth': son
| justamente las que crean y consultan la sesión del comprador, que ocurre
| antes de que exista sesión alguna.
|
|   - /sso/consume  : su seguridad la aporta el token SSO de un solo uso
|                     (ver ValidateAndConsumeSsoTokenUseCase), no la sesión.
|   - /auth/session : devuelve 'authenticated: false' si no hay sesión; no
|                     expone datos de nadie más que del propio solicitante.
|   - /auth/logout  : cerrar sesión debe funcionar siempre, sea cual sea el
|                     estado de la sesión.
|
*/
Route::post('/sso/consume', ConsumeSsoTokenPOSTController::class);
Route::get('/auth/session', GetTenantCustomerSessionGETController::class);
Route::post('/auth/logout', CustomerLogoutPOSTController::class);

/*
|--------------------------------------------------------------------------
| BACKOFFICE — Directorio de clientes de la tienda
|--------------------------------------------------------------------------
|
| Antes estaban abiertas (hallazgo A5): cualquiera podía listar, editar o
| borrar la base de clientes de una tienda, con sus direcciones y datos de
| contacto, con un simple GET a /api-tenant/customer/{id}.
|
*/
Route::middleware('auth')->group(function () {
    Route::get('/metrics', GetCustomerMetricsGETController::class);
    Route::post('/filter', FilterCustomersPOSTController::class);
    Route::post('/create', CreateCustomerPOSTController::class);
    Route::get('/{id}', ConsultCustomerGETController::class);
    Route::put('/{id}', UpdateCustomerPUTController::class);
    Route::delete('/{id}', DeleteCustomerDELETEController::class);

    // Direcciones del cliente
    Route::post('/{id}/address', AddCustomerAddressPOSTController::class);
    Route::delete('/{id}/address/{addressId}', DeleteCustomerAddressDELETEController::class);
    Route::post('/{id}/address/{addressId}/default', SetDefaultCustomerAddressPOSTController::class);
});
