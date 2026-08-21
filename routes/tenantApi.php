<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Tenant API Routes
|--------------------------------------------------------------------------
|
| Montadas desde bootstrap/app.php bajo ['web', InitializeTenancyByDomain,
| PreventAccessFromCentralDomains] y el prefijo /api-tenant.
|
| Hasta la Fase 0.3-E ninguna de estas rutas tenía middleware de
| autenticación (hallazgo A5). Cualquiera en internet podía hacer
| POST https://tienda.owomarket.com/api-tenant/coupon/create con un cupón
| del 100%, DELETE /api-tenant/product/{id} para borrar el catálogo entero,
| o leer la facturación de la tienda — sin iniciar sesión.
|
| Ahora hay tres categorías:
|
|   1. Comunicación interna entre servicios → InternalServiceMiddleware
|      (ya estaba, se declara dentro de los propios archivos del módulo).
|   2. Backoffice de la tienda → 'auth': exige sesión de usuario del
|      inquilino, la misma que crea src/Authentication/.../tenant.php.
|   3. Storefront público → sin sesión, lista blanca EXPLÍCITA y mínima.
|      Estas conviven con rutas de backoffice dentro del mismo módulo, así
|      que su protección se declara dentro del archivo del módulo, no aquí.
|
| 'auth' se aplica aquí (no en bootstrap/app.php) para que corra DESPUÉS de
| InitializeTenancyByDomain y resuelva el usuario contra la base de datos
| del inquilino, no la central.
|
*/

/*
| 1. Servicios internos: ya protegidos con InternalServiceMiddleware dentro
|    de cada archivo. No llevan 'auth' porque no hay un usuario en sesión:
|    la autenticación es por secreto compartido entre servicios.
*/
Route::prefix('auth')->group(callback: base_path('src/Authentication/Infrastructure/Http/Routes/apiTenant.php'));
Route::prefix('user')->group(callback: base_path('src/User/Infrastructure/Http/Routes/apiTenant.php'));

/*
| 2. Módulos 100% de backoffice: todas sus rutas exigen sesión de usuario
|    de la tienda. Ningún consumidor público del storefront los usa
|    (verificado en resources/js: las páginas de marketplace sólo llaman a
|    CentralMarketplaceServices, ExchangeRateServices, StorefrontServices,
|    ReviewServices.create y CouponServices.validate).
*/
Route::middleware('auth')->group(function () {
    Route::prefix('product')->group(callback: base_path('src/Product/Infrastructure/Http/Routes/apiTenant.php'));
    Route::prefix('category')->group(callback: base_path('src/Category/Infrastructure/Http/Routes/apiTenant.php'));
    Route::prefix('brand')->group(callback: base_path('src/Brand/Infrastructure/Http/Routes/apiTenant.php'));
    Route::prefix('attribute')->group(callback: base_path('src/Attribute/Infrastructure/Http/Routes/apiTenant.php'));
    Route::prefix('tax')->group(callback: base_path('src/Tax/Infrastructure/Http/Routes/apiTenant.php'));
    Route::prefix('shipping')->group(callback: base_path('src/Shipping/Infrastructure/Http/Routes/apiTenant.php'));
    Route::prefix('billing')->group(callback: base_path('src/Billing/Infrastructure/Http/Routes/apiTenant.php'));
    Route::prefix('payment')->group(callback: base_path('src/Payment/Infrastructure/Http/Routes/apiTenant.php'));
    Route::prefix('order')->group(callback: base_path('src/Order/Infrastructure/Http/Routes/apiTenant.php'));
    Route::prefix('shipment')->group(callback: base_path('src/Shipment/Infrastructure/Http/Routes/apiTenant.php'));
    Route::prefix('settings')->group(callback: base_path('src/TenantSettings/Infrastructure/Http/Routes/apiTenant.php'));
});

/*
| 3. Módulos mixtos: contienen rutas de backoffice Y rutas que el storefront
|    público necesita. Cada archivo declara su propio grupo 'auth' alrededor
|    de las de backoffice y deja fuera, comentada una por una, la lista
|    blanca pública.
*/
Route::prefix('customer')->group(callback: base_path('src/Customer/Infrastructure/Http/Routes/apiTenant.php'));
Route::prefix('coupon')->group(callback: base_path('src/Coupon/Infrastructure/Http/Routes/apiTenant.php'));
Route::prefix('review')->group(callback: base_path('src/Review/Infrastructure/Http/Routes/apiTenant.php'));
