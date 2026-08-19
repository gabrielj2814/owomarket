<?php

declare(strict_types=1);

use Src\Monetization\Infrastructure\Http\Controller\ConfirmCommissionSettlementPOSTController;
use Src\Monetization\Infrastructure\Http\Controller\GenerateCommissionSettlementPOSTController;
use Src\Monetization\Infrastructure\Http\Controller\GetSuperAdminMonetizationMetricsGETController;
use Src\Monetization\Infrastructure\Http\Controller\ListCommissionSettlementsGETController;
use Src\Monetization\Infrastructure\Http\Controller\ListPlansGETController;
use Src\Monetization\Infrastructure\Http\Controller\UpdateTenantCustomCommissionPOSTController;

Route::get('/plans', ListPlansGETController::class);
Route::post('/custom-commission', UpdateTenantCustomCommissionPOSTController::class);

Route::get('/metrics', GetSuperAdminMonetizationMetricsGETController::class);
Route::get('/settlements', ListCommissionSettlementsGETController::class);
Route::post('/settlements/generate', GenerateCommissionSettlementPOSTController::class);
Route::post('/settlements/{id}/confirm', ConfirmCommissionSettlementPOSTController::class);
