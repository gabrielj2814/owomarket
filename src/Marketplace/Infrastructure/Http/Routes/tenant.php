<?php

use Illuminate\Support\Facades\Route;
use Src\Marketplace\Infrastructure\Http\Controller\CreateStorefrontOrderPOSTController;
use Src\Marketplace\Infrastructure\Http\Controller\ViewCartTenantGETController;
use Src\Marketplace\Infrastructure\Http\Controller\ViewCatalogTenantGETController;
use Src\Marketplace\Infrastructure\Http\Controller\ViewCheckoutTenantGETController;
use Src\Marketplace\Infrastructure\Http\Controller\ViewHomePageTenantGETController;
use Src\Marketplace\Infrastructure\Http\Controller\ViewOrderConfirmationTenantGETController;
use Src\Marketplace\Infrastructure\Http\Controller\ViewProductDetailTenantGETController;

Route::get('/', [ViewHomePageTenantGETController::class, 'index'])->name('tenant.home');
Route::get('/catalog', [ViewCatalogTenantGETController::class, 'index'])->name('tenant.catalog');
Route::get('/product/{slug}', [ViewProductDetailTenantGETController::class, 'index'])->name('tenant.product.detail');
Route::get('/cart', [ViewCartTenantGETController::class, 'index'])->name('tenant.cart');
Route::get('/checkout', [ViewCheckoutTenantGETController::class, 'index'])->name('tenant.checkout');
Route::post('/checkout/create-order', [CreateStorefrontOrderPOSTController::class, 'index'])->name('tenant.checkout.create-order');
Route::get('/order/{id}/confirmation', [ViewOrderConfirmationTenantGETController::class, 'index'])->name('tenant.order.confirmation');
