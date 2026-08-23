<?php

use Illuminate\Support\Facades\Route;
use Src\Authentication\Infrastructure\Http\Controller\CurrentUserGETController;
use Src\Authentication\Infrastructure\Http\Controller\LoginTenantScreenGETController;
use Src\Authentication\Infrastructure\Http\Controller\LoginWebTenantPOSTController;
use Src\Authentication\Infrastructure\Http\Controller\LogoutWebPOSTController;
use Src\Authentication\Infrastructure\Http\Controller\PaginaInicialTestGETController;

Route::get('login', [LoginTenantScreenGETController::class, 'index'])->name('tenant.web.auth.login-tenant');
// Hallazgo N18: consumir un token SSO es canjear una credencial de un solo uso.
Route::get('sso-consume', \Src\Tenant\Infrastructure\Http\Controller\ConsumeTenantOwnerSsoTokenGETController::class)
    ->name('tenant.web.auth.sso-consume')
    ->middleware('throttle:sso');

// Hallazgo N18: sin limite se podia probar contrasenas a ritmo de maquina.
Route::post('/login', [LoginWebTenantPOSTController::class, 'index'])
    ->name('tenant.web.auth.login')
    ->middleware('throttle:credenciales');
Route::post('/logout', [LogoutWebPOSTController::class, 'index'])->name('tenant.web.auth.logout')->middleware('auth');
Route::get('/pagina-inicial', [PaginaInicialTestGETController::class, 'index'])->name('tenant.web.inicial.page')->middleware('auth');
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
Route::get('/user/{user_uuid}', [CurrentUserGETController::class, 'index'])->name('tenant.web.auth.user')->middleware(['auth', 'own_user']);
