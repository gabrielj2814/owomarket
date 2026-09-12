<?php

use Illuminate\Support\Facades\Route;
use Src\Marketplace\Infrastructure\Http\Controller\CreateStorefrontOrderPOSTController;
use Src\Marketplace\Infrastructure\Http\Controller\RevalidateStorefrontCartPOSTController;
use Src\Marketplace\Infrastructure\Http\Controller\ViewCartTenantGETController;
use Src\Marketplace\Infrastructure\Http\Controller\ViewCatalogTenantGETController;
use Src\Marketplace\Infrastructure\Http\Controller\ViewCheckoutTenantGETController;
use Src\Marketplace\Infrastructure\Http\Controller\ViewHomePageTenantGETController;
use Src\Marketplace\Infrastructure\Http\Controller\ViewOrderConfirmationTenantGETController;
use Src\Marketplace\Infrastructure\Http\Controller\ViewProductDetailTenantGETController;
use Src\Marketplace\Infrastructure\Http\Controller\ViewStorefrontMyOrdersGETController;

Route::get('/', [ViewHomePageTenantGETController::class, 'index'])->name('tenant.home');
Route::get('/catalog', [ViewCatalogTenantGETController::class, 'index'])->name('tenant.catalog');
Route::get('/product/{slug}', [ViewProductDetailTenantGETController::class, 'index'])->name('tenant.product.detail');
Route::get('/cart', [ViewCartTenantGETController::class, 'index'])->name('tenant.cart');
Route::post('/cart/revalidate', [RevalidateStorefrontCartPOSTController::class, 'index'])->name('tenant.cart.revalidate');
Route::get('/checkout', [ViewCheckoutTenantGETController::class, 'index'])->name('tenant.checkout');
Route::post('/checkout/create-order', [CreateStorefrontOrderPOSTController::class, 'index'])->name('tenant.checkout.create-order');
Route::get('/order/{id}/confirmation', [ViewOrderConfirmationTenantGETController::class, 'index'])->name('tenant.order.confirmation');

/*
 * Fase 1 de `planes/por_hacer/PLAN_PEDIDOS_ESCAPARATE.md`.
 *
 * El comprador de una tienda no tenia donde ver sus pedidos, asi que no podia confirmar una
 * entrega: TODAS las ventas de escaparate se liberaban por vencimiento del plazo.
 *
 * La pagina es publica --la sesion se comprueba al pedir los datos, no al abrirla-- para que
 * quien llegue sin sesion vea una invitacion a entrar en lugar de un error.
 */
Route::get('/mis-pedidos', [ViewStorefrontMyOrdersGETController::class, 'index'])->name('tenant.my-orders');
