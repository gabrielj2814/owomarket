<?php

use Illuminate\Support\Facades\Route;
use Src\Marketplace\Infrastructure\Http\Controller\ViewCatalogTenantGETController;
use Src\Marketplace\Infrastructure\Http\Controller\ViewHomePageTenantGETController;

Route::get('/', [ViewHomePageTenantGETController::class, 'index'])->name('tenant.home');
Route::get('/catalog', [ViewCatalogTenantGETController::class, 'index'])->name('tenant.catalog');
