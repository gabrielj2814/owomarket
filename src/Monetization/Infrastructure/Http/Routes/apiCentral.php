<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Src\Monetization\Infrastructure\Http\Controller\ListPlansGETController;
use Src\Monetization\Infrastructure\Http\Controller\UpdateTenantCustomCommissionPOSTController;

Route::get('/plans', ListPlansGETController::class);
Route::post('/custom-commission', UpdateTenantCustomCommissionPOSTController::class);
