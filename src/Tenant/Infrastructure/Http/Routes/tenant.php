<?php

use App\Http\Middleware\TenantAuthentication;
use App\Http\Middleware\TenantSanctum;
use App\Http\Middleware\VerifyCrossDomainAuth;
use Illuminate\Support\Facades\Route;
use Src\Tenant\Infrastructure\Http\Controllerl\ViweDashboardTenantGETController;

// Route::get('/',                               [ViweDashboardTenantGETController::class, 'index'])->middleware([VerifyCrossDomainAuth::class,TenantAuthentication::class, TenantSanctum::class]);


?>
