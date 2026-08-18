<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Src\Order\Infrastructure\Http\Controller\ViewOrderDetailGETController;
use Src\Order\Infrastructure\Http\Controller\ViewOrderIndexGETController;

Route::get('/backoffice/{user_uuid}/module', [ViewOrderIndexGETController::class, 'index'])->name('tenant.order.index');
Route::get('/backoffice/{user_uuid}/show/{id}', [ViewOrderDetailGETController::class, 'index'])->name('tenant.order.show');
