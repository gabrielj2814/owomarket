<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Src\Marketplace\Infrastructure\Http\Controller\ConfirmStorefrontDeliveryPOSTController;
use Src\Marketplace\Infrastructure\Http\Controller\GetStorefrontDeliveryStatusGETController;
use Src\Marketplace\Infrastructure\Http\Controller\ListStorefrontMyOrdersGETController;

/*
|--------------------------------------------------------------------------
| El comprador del escaparate y sus propios pedidos
|--------------------------------------------------------------------------
|
| Fase 1 de `planes/por_hacer/PLAN_PEDIDOS_ESCAPARATE.md`.
|
| NO llevan el middleware 'auth': ese guard es el del PERSONAL de la tienda, y el
| comprador de un escaparate no es personal de nadie. Su identidad vive en la sesion
| que crea `/sso/consume` al canjear el token SSO, y cada controlador la resuelve con
| `ResolvesStorefrontCustomer` --que devuelve 401 si no hay nadie dentro--.
|
| Es el mismo patron de lista blanca que ya usan `/sso/consume` y `/auth/session`: son
| rutas del comprador, no del backoffice, y meterlas bajo 'auth' las dejaria
| inalcanzables justo para quien tienen que servir.
|
| Lo que estas tres arreglan: hasta ahora una venta de escaparate no se podia confirmar
| desde ningun sitio, asi que TODAS se liberaban por vencimiento del plazo --con el
| subsistema 3 entero construido y esperando un enlace que el checkout nunca ponia--.
*/
Route::get('/my-orders', ListStorefrontMyOrdersGETController::class);
Route::get('/deliveries/{orderId}', GetStorefrontDeliveryStatusGETController::class);
Route::post('/deliveries/{orderId}/confirm', ConfirmStorefrontDeliveryPOSTController::class);
