<?php

use Illuminate\Support\Facades\Route;
use Src\Authentication\Infrastructure\Http\Controller\CurrentUserGETController;
use Src\Authentication\Infrastructure\Http\Controller\LoginStaffScreenGETController;
use Src\Authentication\Infrastructure\Http\Controller\LoginWebPOSTController;
use Src\Authentication\Infrastructure\Http\Controller\LogoutWebPOSTController;

Route::get('/login', [LoginStaffScreenGETController::class, 'index'])->name('central.web.auth.login-staff');
Route::get('/sso-consume', \Src\Tenant\Infrastructure\Http\Controller\ConsumeTenantOwnerSsoTokenGETController::class)
    ->name('central.web.auth.sso-consume')
    ->middleware('throttle:sso');

// Hallazgo N18: el login del backoffice central no tenia ningun freno.
Route::post('/login', [LoginWebPOSTController::class, 'index'])
    ->name('central.web.auth.login')
    ->middleware('throttle:credenciales');
Route::post('/logout', [LogoutWebPOSTController::class, 'index'])->name('central.web.auth.logout')->middleware('auth');
/*
 * Hallazgo T7. Esta ruta llevaba solo `auth`, y CurrentUserGETController toma el
 * {user_uuid} de la URL y NO lo compara nunca con la sesion. Cualquiera con sesion podia
 * leer el nombre, el correo y el ROL de cualquier otro usuario cambiando el uuid — y la
 * tabla `users` son el personal, los administradores y los propietarios de tienda, asi que
 * un comerciante corriente podia enumerar a los administradores con sus correos.
 *
 * Demostrado: un tenant_owner leyendo el perfil de un super_admin devolvia 200.
 *
 * Salio del barrido de gemelos, y la causa esta ahi: el MISMO controlador esta expuesto
 * tambien como endpoint interno (`api/auth/interna/user/{uuid}`, tras
 * InternalServiceMiddleware), donde consultar a cualquiera es justo su trabajo. La
 * semantica del interno se colo en el de usuario.
 *
 * `own_user` exige que el uuid de la URL sea el de quien pide. Es el mismo alias que ya
 * protegia el panel del propietario desde el hallazgo P1, que era este mismo defecto.
 */
Route::get('/user/{user_uuid}', [CurrentUserGETController::class, 'index'])->name('central.web.auth.user')->middleware(['auth', 'own_user']);
