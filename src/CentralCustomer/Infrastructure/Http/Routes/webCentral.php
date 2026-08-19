<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// Customer Portal Web Routes
Route::prefix('account')->group(function () {
    Route::get('/', fn () => redirect('/account/dashboard'));
    Route::get('/dashboard', fn () => Inertia::render('customer/CustomerDashboardPage'))->name('customer.dashboard');
    Route::get('/profile', fn () => Inertia::render('customer/CustomerProfilePage'))->name('customer.profile');
    Route::get('/addresses', fn () => Inertia::render('customer/CustomerAddressesPage'))->name('customer.addresses');
    Route::get('/orders', fn () => Inertia::render('customer/CustomerOrdersPage'))->name('customer.orders');
    Route::get('/orders/{id}', fn ($id) => Inertia::render('customer/CustomerOrderDetailPage', ['orderId' => $id]))->name('customer.order.detail');
    Route::get('/invoices', fn () => Inertia::render('customer/CustomerInvoicesPage'))->name('customer.invoices');
    Route::get('/returns', fn () => Inertia::render('customer/CustomerReturnsPage'))->name('customer.returns');
    Route::get('/coupons', fn () => Inertia::render('customer/CustomerCouponsPage'))->name('customer.coupons');
    Route::get('/reviews', fn () => Inertia::render('customer/CustomerReviewsPage'))->name('customer.reviews');
    Route::get('/wishlist', fn () => Inertia::render('customer/CustomerWishlistPage'))->name('customer.wishlist');
});

// Auth Password Recovery Web Routes
Route::prefix('auth')->group(function () {
    Route::get('/forgot-password', fn () => Inertia::render('auth/ForgotPasswordPage'))->name('auth.forgot-password');
    Route::get('/reset-password', fn () => Inertia::render('auth/ResetPasswordPage'))->name('auth.reset-password');
});
