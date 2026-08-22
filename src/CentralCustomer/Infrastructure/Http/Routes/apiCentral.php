<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Src\CentralCustomer\Infrastructure\Http\Controller\AddCustomerAddressPOSTController;
use Src\CentralCustomer\Infrastructure\Http\Controller\CreateCustomerReturnPOSTController;
use Src\CentralCustomer\Infrastructure\Http\Controller\CustomerLogoutCentralPOSTController;
use Src\CentralCustomer\Infrastructure\Http\Controller\DeleteCustomerAddressDELETEController;
use Src\CentralCustomer\Infrastructure\Http\Controller\DownloadCustomerInvoicePdfGETController;
use Src\CentralCustomer\Infrastructure\Http\Controller\GenerateSsoTokenPOSTController;
use Src\CentralCustomer\Infrastructure\Http\Controller\GetCustomerOrderDetailGETController;
use Src\CentralCustomer\Infrastructure\Http\Controller\GetCustomerOrderTrackingGETController;
use Src\CentralCustomer\Infrastructure\Http\Controller\GetCustomerProfileGETController;
use Src\CentralCustomer\Infrastructure\Http\Controller\ListCustomerCouponsGETController;
use Src\CentralCustomer\Infrastructure\Http\Controller\ListCustomerInvoicesGETController;
use Src\CentralCustomer\Infrastructure\Http\Controller\ListCustomerOrdersGETController;
use Src\CentralCustomer\Infrastructure\Http\Controller\ListCustomerPendingReviewsGETController;
use Src\CentralCustomer\Infrastructure\Http\Controller\ListCustomerReturnsGETController;
use Src\CentralCustomer\Infrastructure\Http\Controller\ListCustomerWishlistGETController;
use Src\CentralCustomer\Infrastructure\Http\Controller\LoginCentralCustomerPOSTController;
use Src\CentralCustomer\Infrastructure\Http\Controller\RegisterCentralCustomerPOSTController;
use Src\CentralCustomer\Infrastructure\Http\Controller\ResetCustomerPasswordWithPinPOSTController;
use Src\CentralCustomer\Infrastructure\Http\Controller\SendCustomerPasswordResetPinPOSTController;
use Src\CentralCustomer\Infrastructure\Http\Controller\SetDefaultCustomerAddressPATCHController;
use Src\CentralCustomer\Infrastructure\Http\Controller\SubmitCustomerReviewPOSTController;
use Src\CentralCustomer\Infrastructure\Http\Controller\ToggleCustomerWishlistPOSTController;
use Src\CentralCustomer\Infrastructure\Http\Controller\UpdateCustomerAddressPUTController;
use Src\CentralCustomer\Infrastructure\Http\Controller\UpdateCustomerProfilePUTController;

/*
|--------------------------------------------------------------------------
| Autenticación y Recuperación de Cuenta (público, sin sesión)
|--------------------------------------------------------------------------
|
| Este archivo se monta desde routes/api.php, bajo el grupo 'api' de Laravel,
| que NO arranca sesión ni CSRF por defecto. Le añadimos 'web' explícitamente
| en los grupos de abajo (mismo motivo que Monetization en la Fase 0.3-A):
| sin 'web' no hay sesión sobre la que 'auth:central_customer' pueda resolver
| identidad.
|
*/
Route::middleware('web')->group(function () {
    // Hallazgo N18: las cuatro puertas sin sesion del cliente central. El PIN ya
    // llevaba freno desde la Fase 4.1; el alta y el acceso no.
    Route::post('/register', RegisterCentralCustomerPOSTController::class)->middleware('throttle:altas');
    Route::post('/login', LoginCentralCustomerPOSTController::class)->middleware('throttle:credenciales');
    Route::post('/forgot-password', SendCustomerPasswordResetPinPOSTController::class);
    Route::post('/reset-password', ResetCustomerPasswordWithPinPOSTController::class);

    /*
    |----------------------------------------------------------------------
    | Portal del cliente central (hallazgo A3 — requiere sesión)
    |----------------------------------------------------------------------
    |
    | Hasta la Fase 0.3-D estas rutas no tenían NINGÚN middleware y cada
    | controlador tomaba el ID del cliente de la URL, la query string o la
    | cabecera X-Customer-Id — cualquiera podía leer o modificar los datos
    | de cualquier comprador con solo conocer su UUID (perfil, pedidos,
    | facturas, devoluciones, wishlist, reseñas, y el propio 'sso/generate-token',
    | que emitía sesiones de tienda para cualquier customer_id sin verificar
    | contraseña alguna). El guard 'central_customer' (nuevo, ver config/auth.php)
    | resuelve la identidad SIEMPRE desde la sesión que crea LoginCentralCustomerPOSTController;
    | los controladores dejan de confiar en el request.
    |
    */
    Route::middleware('auth:central_customer')->group(function () {
        Route::post('/logout', CustomerLogoutCentralPOSTController::class);
        Route::post('/sso/generate-token', GenerateSsoTokenPOSTController::class)->middleware('throttle:sso');

        // Perfil y Libreta de Direcciones
        Route::get('/profile/{id}', GetCustomerProfileGETController::class);
        Route::put('/profile/{id}', UpdateCustomerProfilePUTController::class);
        Route::post('/profile/{id}/address', AddCustomerAddressPOSTController::class);
        Route::put('/profile/{id}/address/{address_id}', UpdateCustomerAddressPUTController::class);
        Route::delete('/profile/{id}/address/{address_id}', DeleteCustomerAddressDELETEController::class);
        Route::patch('/profile/{id}/address/{address_id}/default', SetDefaultCustomerAddressPATCHController::class);

        // Historial de Pedidos y Tracking en Vivo
        Route::get('/orders', ListCustomerOrdersGETController::class);
        Route::get('/orders/{id}', GetCustomerOrderDetailGETController::class);
        Route::get('/orders/{id}/tracking', GetCustomerOrderTrackingGETController::class);

        // Facturación Electrónica y Descarga PDF
        Route::get('/invoices', ListCustomerInvoicesGETController::class);
        Route::get('/invoices/{id}/pdf', DownloadCustomerInvoicePdfGETController::class);

        // Devoluciones y Reclamaciones (RMA)
        Route::post('/returns', CreateCustomerReturnPOSTController::class);
        Route::get('/returns', ListCustomerReturnsGETController::class);

        // Reseñas y Calificaciones de Productos
        Route::get('/reviews/pending', ListCustomerPendingReviewsGETController::class);
        Route::post('/reviews', SubmitCustomerReviewPOSTController::class);

        // Lista de Deseos / Favoritos (Wishlist)
        Route::post('/wishlist/toggle', ToggleCustomerWishlistPOSTController::class);
        Route::get('/wishlist', ListCustomerWishlistGETController::class);
    });

    // Cupones y Promociones Activas: catálogo público, no depende del comprador.
    Route::get('/coupons', ListCustomerCouponsGETController::class);
});
