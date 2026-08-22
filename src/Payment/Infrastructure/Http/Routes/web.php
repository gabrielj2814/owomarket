<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Src\Payment\Infrastructure\Http\Controller\AdminUpdateCentralPaymentSettingsPUTController;
use Src\Payment\Infrastructure\Http\Controller\AdminViewCentralPaymentSettingsGETController;

/*
 * Hallazgo N33: la Fase 3.4 dejo el checkout central leyendo `central_settings` y no habia
 * donde escribirlos. Estos datos deciden a que cuenta transfiere el comprador de un pedido
 * multi-tienda, asi que van bajo `super_admin`, no bajo un permiso de staff.
 */
Route::middleware(['auth', 'super_admin'])->group(function () {
    Route::get('/backoffice/{user_uuid}/payment-settings', AdminViewCentralPaymentSettingsGETController::class)
        ->name('central.backoffice.web.admin.payment_settings.index');

    Route::put('/backoffice/payment-settings', AdminUpdateCentralPaymentSettingsPUTController::class)
        ->name('central.backoffice.web.admin.payment_settings.update');
});
