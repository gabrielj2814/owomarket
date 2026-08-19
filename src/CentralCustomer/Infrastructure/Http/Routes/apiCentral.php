<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Src\CentralCustomer\Infrastructure\Http\Controller\AddCustomerAddressPOSTController;
use Src\CentralCustomer\Infrastructure\Http\Controller\CreateCustomerReturnPOSTController;
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

// Autenticación y Recuperación de Cuenta
Route::post('/register', RegisterCentralCustomerPOSTController::class);
Route::post('/login', LoginCentralCustomerPOSTController::class);
Route::post('/forgot-password', SendCustomerPasswordResetPinPOSTController::class);
Route::post('/reset-password', ResetCustomerPasswordWithPinPOSTController::class);
Route::post('/sso/generate-token', GenerateSsoTokenPOSTController::class);

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

// Cupones y Promociones Activas
Route::get('/coupons', ListCustomerCouponsGETController::class);

// Reseñas y Calificaciones de Productos
Route::get('/reviews/pending', ListCustomerPendingReviewsGETController::class);
Route::post('/reviews', SubmitCustomerReviewPOSTController::class);

// Lista de Deseos / Favoritos (Wishlist)
Route::post('/wishlist/toggle', ToggleCustomerWishlistPOSTController::class);
Route::get('/wishlist', ListCustomerWishlistGETController::class);
