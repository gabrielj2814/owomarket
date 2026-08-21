<?php

declare(strict_types=1);

use Src\Monetization\Infrastructure\Http\Controller\ConfirmCommissionSettlementPOSTController;
use Src\Monetization\Infrastructure\Http\Controller\GenerateCommissionSettlementPOSTController;
use Src\Monetization\Infrastructure\Http\Controller\GetSuperAdminMonetizationMetricsGETController;
use Src\Monetization\Infrastructure\Http\Controller\ListCommissionSettlementsGETController;
use Src\Monetization\Infrastructure\Http\Controller\ListPlansGETController;
use Src\Monetization\Infrastructure\Http\Controller\UpdateTenantCustomCommissionPOSTController;

/*
|--------------------------------------------------------------------------
| API de Monetización — Plataforma Central
|--------------------------------------------------------------------------
|
| Montado en routes/api.php con el prefijo 'central/monetization'.
|
| Estas rutas gobiernan el dinero de la plataforma: la tasa de comisión que se
| cobra a cada tienda, la generación de liquidaciones y su confirmación como
| cobradas. Todas exigen sesión de super administrador.
|
| Nota: el grupo 'api' de Laravel NO incluye sesión por defecto, pero estas rutas
| se sirven al backoffice desde el mismo navegador, por lo que 'web' aporta la
| sesión sobre la que 'auth' y 'super_admin' resuelven la identidad.
|
*/

Route::middleware(['web', 'auth', 'super_admin'])->group(function () {
    Route::get('/plans', ListPlansGETController::class);
    Route::post('/custom-commission', UpdateTenantCustomCommissionPOSTController::class);

    Route::get('/metrics', GetSuperAdminMonetizationMetricsGETController::class);
    Route::get('/settlements', ListCommissionSettlementsGETController::class);
    Route::post('/settlements/generate', GenerateCommissionSettlementPOSTController::class);
    Route::post('/settlements/{id}/confirm', ConfirmCommissionSettlementPOSTController::class);
});
