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
/*
| Hallazgo N19 — control de rol dentro de la tienda.
|
| Hasta ahora estos modulos solo exigian 'auth': cualquiera con sesion en la tienda,
| incluido un `staff` recien contratado, podia borrar el catalogo entero o anular
| facturas exactamente igual que el propietario.
|
| `tenant_can` solo exige permiso en los metodos que ESCRIBEN. Un `staff` sigue pudiendo
| consultar catalogo, pedidos y facturacion —lo necesita para trabajar—; lo que no puede
| es modificarlos sin que se lo hayan concedido. Y el propietario pasa siempre.
|
| Los permisos se agrupan por area de responsabilidad, no por modulo: quien lleva el
| catalogo toca productos, categorias, marcas y atributos, y no tiene sentido concederlos
| por separado.
*/
Route::middleware('auth')->group(function () {
    Route::middleware('tenant_can:manage_catalog')->group(function () {
        Route::prefix('product')->group(callback: base_path('src/Product/Infrastructure/Http/Routes/apiTenant.php'));
        Route::prefix('category')->group(callback: base_path('src/Category/Infrastructure/Http/Routes/apiTenant.php'));
        Route::prefix('brand')->group(callback: base_path('src/Brand/Infrastructure/Http/Routes/apiTenant.php'));
        Route::prefix('attribute')->group(callback: base_path('src/Attribute/Infrastructure/Http/Routes/apiTenant.php'));
    });

    Route::middleware('tenant_can:manage_orders')->group(function () {
        Route::prefix('order')->group(callback: base_path('src/Order/Infrastructure/Http/Routes/apiTenant.php'));
        Route::prefix('shipment')->group(callback: base_path('src/Shipment/Infrastructure/Http/Routes/apiTenant.php'));
    });

    // Facturacion y cobros van juntos: anular una factura y tocar un pago son la misma
    // clase de decision, y quien pueda una deberia poder la otra.
    Route::middleware('tenant_can:manage_billing')->group(function () {
        Route::prefix('billing')->group(callback: base_path('src/Billing/Infrastructure/Http/Routes/apiTenant.php'));
        Route::prefix('payment')->group(callback: base_path('src/Payment/Infrastructure/Http/Routes/apiTenant.php'));
    });

    // Impuestos, envios y ajustes de la tienda cambian lo que se le cobra a TODOS los
    // clientes, asi que van al permiso mas restrictivo del conjunto.
    Route::middleware('tenant_can:manage_settings')->group(function () {
        Route::prefix('tax')->group(callback: base_path('src/Tax/Infrastructure/Http/Routes/apiTenant.php'));
        Route::prefix('shipping')->group(callback: base_path('src/Shipping/Infrastructure/Http/Routes/apiTenant.php'));
        Route::prefix('settings')->group(callback: base_path('src/TenantSettings/Infrastructure/Http/Routes/apiTenant.php'));
    });
});

/*
| 3. Módulos mixtos: contienen rutas de backoffice Y rutas que el storefront
|    público necesita. Cada archivo declara su propio grupo 'auth' alrededor
|    de las de backoffice y deja fuera, comentada una por una, la lista
|    blanca pública.
*/
/*
| Estos tres no se pueden envolver aqui: su grupo 'auth' esta DENTRO del archivo del
| modulo, porque conviven con rutas publicas del storefront. Un `tenant_can` a este nivel
| tambien alcanzaria a las publicas y dejaria al comprador sin poder escribir una resena
| ni validar un cupon. El permiso se declara dentro de cada archivo, sobre su grupo
| privado (hallazgo N19).
*/
Route::prefix('customer')->group(callback: base_path('src/Customer/Infrastructure/Http/Routes/apiTenant.php'));
Route::prefix('coupon')->group(callback: base_path('src/Coupon/Infrastructure/Http/Routes/apiTenant.php'));
Route::prefix('review')->group(callback: base_path('src/Review/Infrastructure/Http/Routes/apiTenant.php'));
