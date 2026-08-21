<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Src\Review\Infrastructure\Http\Controller\ConsultReviewGETController;
use Src\Review\Infrastructure\Http\Controller\CreateProductReviewPOSTController;
use Src\Review\Infrastructure\Http\Controller\DeleteProductReviewDELETEController;
use Src\Review\Infrastructure\Http\Controller\FilterReviewsPOSTController;
use Src\Review\Infrastructure\Http\Controller\GetProductRatingSummaryGETController;
use Src\Review\Infrastructure\Http\Controller\ModerateReviewPOSTController;
use Src\Review\Infrastructure\Http\Controller\RespondReviewPOSTController;
use Src\Review\Infrastructure\Http\Controller\UpdateProductReviewPUTController;

/*
|--------------------------------------------------------------------------
| PÚBLICA — Publicación de reseña desde la ficha de producto
|--------------------------------------------------------------------------
|
| Lista blanca de la Fase 0.3-E. La consume TenantProductDetailPage.tsx
| (única llamada de esa página a /api-tenant). El resto de la ficha —el
| listado de reseñas y el resumen de valoraciones— llega por props de
| Inertia desde ViewProductDetailTenantGETController, así que 'filter' y
| 'summary' NO necesitan ser públicas y quedan bajo 'auth'.
|
| Sigue pública a propósito (decisión de la Fase 0.3-E), pero desde la
| Fase 0.4 eso ya no permite falsear el contenido: el hallazgo B2 está
| cerrado. 'is_approved' e 'is_verified' dejaron de aceptarse del cuerpo de
| la petición — la reseña nace pendiente de moderación y la insignia de
| "compra verificada" la concede el servidor comprobando que el pedido sea
| de quien reseña y contenga el producto (ver VerifiedPurchaseChecker).
|
| Lo que sigue pendiente aquí es de identidad, no de contenido: 'customer_id'
| llega en el cuerpo, así que un anónimo puede reseñar a nombre de otro
| cliente existente. Exigir session('tenant_customer_id') es el cierre
| natural, y además arreglaría que hoy el formulario del storefront NO envía
| customer_id (TenantProductDetailPage.tsx:144-151) y por tanto recibe 422.
|
*/
Route::post('create', CreateProductReviewPOSTController::class);

/*
|--------------------------------------------------------------------------
| BACKOFFICE — Moderación y gestión de reseñas
|--------------------------------------------------------------------------
|
| Antes estaban abiertas (hallazgo A5): cualquiera podía aprobar, responder
| como la tienda, editar o borrar cualquier reseña.
|
*/
Route::middleware('auth')->group(function () {
    Route::post('filter', FilterReviewsPOSTController::class);
    Route::get('summary/{productId?}', GetProductRatingSummaryGETController::class);
    Route::get('{id}', ConsultReviewGETController::class);
    Route::post('{id}/moderate', ModerateReviewPOSTController::class);
    Route::post('{id}/respond', RespondReviewPOSTController::class);
    Route::put('{id}', UpdateProductReviewPUTController::class);
    Route::delete('{id}', DeleteProductReviewDELETEController::class);
});
