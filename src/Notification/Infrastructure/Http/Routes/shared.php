<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Src\Notification\Infrastructure\Http\Controller\ListNotificationsGETController;
use Src\Notification\Infrastructure\Http\Controller\MarkNotificationsReadPOSTController;

/*
|--------------------------------------------------------------------------
| El buzon, para cualquier audiencia
|--------------------------------------------------------------------------
|
| Este fichero NO declara guard: lo monta quien lo incluye, dentro de su propio grupo
| autenticado. El personal lo monta bajo `auth` y el comprador bajo `auth:central_customer`,
| y `$request->user()` devuelve el destinatario que corresponda en cada caso.
|
| Se hace asi y no con dos juegos de rutas porque el buzon es el mismo para los dos: lo unico
| que cambia es quien pregunta. Dos copias se separarian en cuanto alguien tocara una.
*/
Route::get('/notifications', ListNotificationsGETController::class);
Route::post('/notifications/read', MarkNotificationsReadPOSTController::class);
