<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Src\Marketplace\Infrastructure\Http\Controller\ViewCartCentralGETController;
use Src\Marketplace\Infrastructure\Http\Controller\ViewCatalogCentralGETController;
use Src\Marketplace\Infrastructure\Http\Controller\ViewCheckoutCentralGETController;
use Src\Marketplace\Infrastructure\Http\Controller\ViewHomePageCentralGETController;
use Src\Marketplace\Infrastructure\Http\Controller\ViewOrderConfirmationCentralGETController;
use Src\Marketplace\Infrastructure\Http\Controller\ViewProductDetailCentralGETController;

Route::get('/', [ViewHomePageCentralGETController::class, 'index'])->name('central.home');
Route::get('/marketplace', [ViewCatalogCentralGETController::class, 'index'])->name('central.catalog');
Route::get('/product/{slug}', [ViewProductDetailCentralGETController::class, 'index'])->name('central.product');
Route::get('/cart', [ViewCartCentralGETController::class, 'index'])->name('central.cart');
Route::get('/checkout', [ViewCheckoutCentralGETController::class, 'index'])->name('central.checkout');
Route::get('/central/order/{id}/confirmation', [ViewOrderConfirmationCentralGETController::class, 'index'])->name('central.order.confirmation');
