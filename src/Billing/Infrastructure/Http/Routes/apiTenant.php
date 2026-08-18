<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Src\Billing\Infrastructure\Http\Controller\ConsultBillingProfileGETController;
use Src\Billing\Infrastructure\Http\Controller\UpdateBillingProfilePUTController;

// Billing Profile Endpoints
Route::get('/profile', ConsultBillingProfileGETController::class);
Route::put('/profile', UpdateBillingProfilePUTController::class);
