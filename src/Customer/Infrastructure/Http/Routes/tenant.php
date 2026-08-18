<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Src\Customer\Infrastructure\Http\Controller\ViewCustomerDetailGETController;
use Src\Customer\Infrastructure\Http\Controller\ViewCustomerIndexGETController;

Route::get('/backoffice/{user_uuid}/module', [ViewCustomerIndexGETController::class, 'index'])->name('tenant.customer.index');
Route::get('/backoffice/{user_uuid}/show/{id}', [ViewCustomerDetailGETController::class, 'index'])->name('tenant.customer.show');
