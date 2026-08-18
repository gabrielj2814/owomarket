<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Tenant API Routes
|--------------------------------------------------------------------------
*/

Route::prefix('auth')->group(callback: base_path('src/Authentication/Infrastructure/Http/Routes/apiTenant.php'));
Route::prefix('user')->group(callback: base_path('src/User/Infrastructure/Http/Routes/apiTenant.php'));
Route::prefix('product')->group(callback: base_path('src/Product/Infrastructure/Http/Routes/apiTenant.php'));
Route::prefix('category')->group(callback: base_path('src/Category/Infrastructure/Http/Routes/apiTenant.php'));
Route::prefix('brand')->group(callback: base_path('src/Brand/Infrastructure/Http/Routes/apiTenant.php'));
Route::prefix('attribute')->group(callback: base_path('src/Attribute/Infrastructure/Http/Routes/apiTenant.php'));
Route::prefix('coupon')->group(callback: base_path('src/Coupon/Infrastructure/Http/Routes/apiTenant.php'));
Route::prefix('tax')->group(callback: base_path('src/Tax/Infrastructure/Http/Routes/apiTenant.php'));
Route::prefix('shipping')->group(callback: base_path('src/Shipping/Infrastructure/Http/Routes/apiTenant.php'));
Route::prefix('billing')->group(callback: base_path('src/Billing/Infrastructure/Http/Routes/apiTenant.php'));
Route::prefix('payment')->group(callback: base_path('src/Payment/Infrastructure/Http/Routes/apiTenant.php'));
Route::prefix('customer')->group(callback: base_path('src/Customer/Infrastructure/Http/Routes/apiTenant.php'));
