<?php

declare(strict_types=1);

use Src\Monetization\Infrastructure\Http\Controller\GetTenantMonetizationSummaryGETController;
use Src\Monetization\Infrastructure\Http\Controller\GetTenantSettlementHistoryGETController;
use Src\Monetization\Infrastructure\Http\Controller\ListPlansGETController;
use Src\Monetization\Infrastructure\Http\Controller\ReportTenantSettlementPaymentPOSTController;

/*
|--------------------------------------------------------------------------
| Hallazgo T5 — estas rutas no tenian NINGUN middleware
|--------------------------------------------------------------------------
|
| Ni `auth`, ni comprobacion de propietario, ni limite de tasa. Lo unico delante era la
| resolucion de tenancy por dominio, asi que en el escaparate de cualquier tienda un
| visitante anonimo podia operar sobre su facturacion.
|
| **Demostrado ejecutando:** un POST sin sesion a `/monetization/subscribe` devolvia 200 y
| cambiaba la suscripcion de la tienda. Con el plan viaja la `commission_rate`, o sea lo
| que la plataforma cobra por cada venta; la propia respuesta lo anunciaba con un
| «Ahora disfrutas de una comision reducida».
|
| El caso realista no era el vandalo, era el comerciante regalandose el plan mas barato.
|
| `/subscribe` se BORRA, no se protege. Aunque exigiera sesion, seguiria permitiendo que
| una tienda se cambie el plan por su cuenta — que es exactamente lo que el flujo de
| solicitud y aprobacion del hallazgo T3 existe para impedir. Dos puertas a la misma
| decision y una sin portero es como se pierde el control de las comisiones.
|
| El resto queda tras `auth` y `tenant_can:manage_billing`, el mismo par que ya protegia
| `/api-tenant/payment/process`, que es la misma clase de operacion.
*/
// Reportar el pago de una liquidacion escribe `payment_reference` sobre una fila de dinero:
// sin sesion, cualquiera podia dejarle al administrador una referencia bancaria inventada
// sobre las liquidaciones de esa tienda. Mismo par que ya protegia /api-tenant/payment/process.
Route::post('/settlements/pay', ReportTenantSettlementPaymentPOSTController::class)
    ->middleware(['auth', 'tenant_can:manage_billing', 'throttle:credenciales'])
    ->name('tenant.monetization.settlements.pay');

/*
 * Hallazgo T6: estos dos eran publicos. `/summary` expone la tarifa de comision que paga la
 * tienda y `/settlements` su historial de liquidaciones — datos de negocio que un competidor
 * leia con solo pasar por el escaparate.
 *
 * Llevan `auth` y NO `tenant_can:manage_billing`, y la ausencia es deliberada:
 * EnsureTenantUserHasPermission deja pasar todas las lecturas sin comprobar nada
 * (`esLectura()`), por decision documentada — un `staff` tiene que poder consultar la
 * facturacion para trabajar; lo que no puede es modificarla. Ponerlo aqui no anadiria ni
 * una comprobacion y haria creer al siguiente lector que este GET esta filtrado por permiso.
 */
Route::middleware('auth')->group(function () {
    Route::get('/summary', GetTenantMonetizationSummaryGETController::class)->name('tenant.monetization.summary');
    Route::get('/settlements', GetTenantSettlementHistoryGETController::class)->name('tenant.monetization.settlements');
});

// El catalogo de planes es informacion comercial publica y no depende de quien pregunte.
Route::get('/plans', ListPlansGETController::class)->name('tenant.monetization.plans');
