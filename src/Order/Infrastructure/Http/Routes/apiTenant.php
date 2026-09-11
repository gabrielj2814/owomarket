<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Src\Monetization\Infrastructure\Http\Controller\AttachShipmentEvidencePOSTController;
use Src\Monetization\Infrastructure\Http\Controller\GetOrderDeliveryStatusGETController;
use Src\Order\Infrastructure\Http\Controller\CancelOrderPOSTController;
use Src\Order\Infrastructure\Http\Controller\ConsultOrderByOrderNumberGETController;
use Src\Order\Infrastructure\Http\Controller\ConsultOrderGETController;
use Src\Order\Infrastructure\Http\Controller\CreateOrderPOSTController;
use Src\Order\Infrastructure\Http\Controller\FilterOrdersPOSTController;
use Src\Order\Infrastructure\Http\Controller\GetOrderMetricsGETController;
use Src\Order\Infrastructure\Http\Controller\ReportOrderPaymentReferencePOSTController;
use Src\Order\Infrastructure\Http\Controller\UpdateOrderPaymentStatusPOSTController;
use Src\Order\Infrastructure\Http\Controller\UpdateOrderStatusPOSTController;

Route::get('/metrics', GetOrderMetricsGETController::class);
Route::post('/filter', FilterOrdersPOSTController::class);
Route::post('/create', CreateOrderPOSTController::class);
Route::get('/number/{orderNumber}', ConsultOrderByOrderNumberGETController::class);
Route::get('/{id}', ConsultOrderGETController::class);
Route::post('/{id}/status', UpdateOrderStatusPOSTController::class);
Route::post('/{id}/cancel', CancelOrderPOSTController::class);
Route::post('/{id}/payment-status', UpdateOrderPaymentStatusPOSTController::class);

/*
 * Fase 3b: el comerciante ya no declara que un pago llego --lo confirma la plataforma, que es
 * donde entra el dinero-- pero si puede reportar la referencia que el comprador le paso por
 * otro canal. Es una pista para cuadrar un deposito, no un hecho.
 */
Route::post('/{id}/report-payment', ReportOrderPaymentReferencePOSTController::class);

/*
 * Subsistema 3, fase B: el comerciante deja constancia de que envio el pedido, con fotos o
 * video. **Subir evidencia NO libera dinero** --eso lo hace el comprador al confirmar, o el
 * plazo al vencer-- asi que que esta ruta viva en la API de la tienda no reabre el agujero
 * que cerro la fase A. Lo que hace es que «lo envie» contra «no me llego» deje de ser la
 * palabra de uno contra la del otro.
 */
Route::post('/{orderId}/shipment-evidence', AttachShipmentEvidencePOSTController::class);
Route::get('/{orderId}/delivery-status', GetOrderDeliveryStatusGETController::class);
