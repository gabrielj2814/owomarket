<?php

use Illuminate\Support\Facades\Route;
use Src\Admin\Infrastructure\Http\Controller\ViewDashboardAdminGETController;

Route::get('/{user_uuid}/dashboard', [ViewDashboardAdminGETController::class, 'index'])->name('central.backoffice.web.admin.dashboard');


?>
