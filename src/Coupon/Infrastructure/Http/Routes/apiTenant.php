<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Src\Coupon\Infrastructure\Http\Controller\ConsultCouponGETController;
use Src\Coupon\Infrastructure\Http\Controller\CreateCouponPOSTController;
use Src\Coupon\Infrastructure\Http\Controller\DeleteCouponDELETEController;
use Src\Coupon\Infrastructure\Http\Controller\EditCouponPUTController;
use Src\Coupon\Infrastructure\Http\Controller\FilterCouponsPOSTController;
use Src\Coupon\Infrastructure\Http\Controller\ValidateCouponPOSTController;

/*
|--------------------------------------------------------------------------
| PÚBLICA — Validación de cupón en el carrito del storefront
|--------------------------------------------------------------------------
|
| Lista blanca de la Fase 0.3-E. La consume TenantCartPage.tsx cuando un
| comprador anónimo escribe un código en el carrito, antes de identificarse.
| Sólo lee: comprueba el código y devuelve el descuento calculado; no crea,
| modifica ni consume nada.
|
| OJO — sigue pendiente el hallazgo B3: el checkout real
| (CreateStorefrontOrderPOSTController) NO usa este endpoint ni
| ValidateCouponUseCase, sino que aplica el cupón comprobando sólo
| 'is_active', ignorando fechas, límites de uso y monto mínimo. Esta ruta
| ser pública no es el problema; el problema es que el checkout no la use.
|
*/
Route::post('/validate', ValidateCouponPOSTController::class);

/*
|--------------------------------------------------------------------------
| BACKOFFICE — CRUD de cupones
|--------------------------------------------------------------------------
|
| Antes estaban abiertas (hallazgo A5). El escenario textual de la auditoría:
| POST /api-tenant/coupon/create {"code":"FREE","type":"percentage","value":100}
| creaba un cupón del 100% desde internet, sin login, y luego se usaba.
|
*/
// Hallazgo N19: Un cupon es dinero: quien pueda crearlos puede regalar el margen de la tienda. Las lecturas siguen abiertas al `staff`;
// `tenant_can` solo exige el permiso al escribir.
Route::middleware(['auth', 'tenant_can:manage_coupons'])->group(function () {
    Route::post('/create', CreateCouponPOSTController::class);
    Route::post('/filter', FilterCouponsPOSTController::class);
    Route::get('/{id}', ConsultCouponGETController::class);
    Route::put('/{id}', EditCouponPUTController::class);
    Route::delete('/{id}', DeleteCouponDELETEController::class);
});
