<?php

use Illuminate\Support\Facades\Route;
use Src\Tenant\Infrastructure\Http\Controller\ActiveTenantByUuidPATCHController;
use Src\Tenant\Infrastructure\Http\Controller\ApprovedTenantByUuidPATCHController;
use Src\Tenant\Infrastructure\Http\Controller\ConsultTenantByUuidGETController;
use Src\Tenant\Infrastructure\Http\Controller\ConsultTenantByUuidOfOwnerPOSTController;
use Src\Tenant\Infrastructure\Http\Controller\CreateAccountTenantPOSTController;
use Src\Tenant\Infrastructure\Http\Controller\CreateTenantPOSTController;
use Src\Tenant\Infrastructure\Http\Controller\DeleteTenantDELETEController;
use Src\Tenant\Infrastructure\Http\Controller\FiltrarTenantsPOSTController;
use Src\Tenant\Infrastructure\Http\Controller\InactiveTenantByUuidPATCHController;
use Src\Tenant\Infrastructure\Http\Controller\RejectedTenantByUuidPATCHController;
use Src\Tenant\Infrastructure\Http\Controller\SuspendedTenantByUuidPATCHController;
use Src\Tenant\Infrastructure\Http\Controller\ViewCreateAccountTenantGETController;
use Src\Tenant\Infrastructure\Http\Controller\ViewDashboardCentralTenantOwnerIndexGETController;
use Src\Tenant\Infrastructure\Http\Controller\ViewModuleTenantIndexGETController;
use Src\Tenant\Infrastructure\Http\Controller\ViewModuleTenantRequestIndexGETController;
use Src\Tenant\Infrastructure\Http\Controller\ViewModuleTenantSuspendedIndexGETController;

/*
|--------------------------------------------------------------------------
| Gobernanza de tiendas — administración de la plataforma (hallazgo P0)
|--------------------------------------------------------------------------
|
| Todo este bloque llevaba sólo 'auth'. Cualquiera con sesión en el hub central —un
| propietario de tienda, sin ir más lejos— podía suspender, aprobar o rechazar la tienda de
| otro comerciante, y leer el listado completo de tiendas de la plataforma.
|
| Y había algo peor: las tres rutas `/admin/api/tenants/{id}/…` estaban declaradas TAMBIÉN
| aquí sin rol, mientras sus gemelas de `src/Admin/.../web.php` exigen `super_admin` o
| `staff:manage_tenants`. El duplicado esquivaba al portero. Lo que quedaba abierto por ese
| camino era emitir un token SSO de CUALQUIER tienda, y ese token se consume con
| `Auth::login()`: entrar a la tienda ajena como su dueño.
|
| Se borran los tres duplicados. El frontend siempre llamó a las protegidas —ver
| `AdminTenantDetail360Page.tsx:137,156,199`— así que las de aquí no las usaba nadie:
| sólo estaban abiertas.
*/
Route::middleware(['auth', 'staff:manage_tenants'])->group(function () {
    // Pantallas del módulo de tiendas
    Route::get('/backoffice/{user_uuid}/module', [ViewModuleTenantIndexGETController::class, 'index'])->name('central.backoffice.web.admin.module.tenant.index');
    Route::get('/backoffice/{user_uuid}/module/suspended', [ViewModuleTenantSuspendedIndexGETController::class, 'index'])->name('central.backoffice.web.admin.module.tenant.suspended');
    Route::get('/backoffice/{user_uuid}/module/request', [ViewModuleTenantRequestIndexGETController::class, 'index'])->name('central.backoffice.web.admin.module.tenant.request');
    Route::get('/backoffice/{user_uuid}/module/tenant/{id}/360', \Src\Tenant\Infrastructure\Http\Controller\ViewAdminTenantDetail360PageGETController::class)->name('central.backoffice.web.admin.module.tenant.360');

    // Consulta
    Route::post('/backoffice/filter', [FiltrarTenantsPOSTController::class, 'index']);
    Route::get('/backoffice/{id}', [ConsultTenantByUuidGETController::class, 'index']);

    // Cambios de estado de una tienda
    Route::patch('/backoffice/{id}/suspended', [SuspendedTenantByUuidPATCHController::class, 'index']);
    Route::patch('/backoffice/{id}/active', [ActiveTenantByUuidPATCHController::class, 'index']);
    Route::patch('/backoffice/{id}/inactive', [InactiveTenantByUuidPATCHController::class, 'index']);

    // Resolución de solicitudes de alta
    Route::patch('/backoffice/{id}/rejected', [RejectedTenantByUuidPATCHController::class, 'index']);
    Route::patch('/backoffice/{id}/approved', [ApprovedTenantByUuidPATCHController::class, 'index']);
});

/*
|--------------------------------------------------------------------------
| Alta publica de tienda (hallazgo A6)
|--------------------------------------------------------------------------
|
| Esta ruta es anonima a proposito: es el formulario de registro de comercios. Su unica
| proteccion posible es el limite de tasa — y no lo tenia. GovernanceRoutesAreGatedTest la
| exime del control de rol razonando que "su proteccion es el limite de tasa, no el rol":
| el razonamiento es correcto, el limite no existia.
|
| (Aqui se dijo que eran TRES los sitios que afirmaban un freno inexistente. Verificado en
| el barrido del 23/08: eran dos —este y el comentario de apiCentral.php—. La cabecera de
| RateLimitingTest hablaba del PIN del ADMINISTRADOR, que si lo tenia desde A7: era una
| frase ambigua, no falsa.)
|
| Lo que costaba una peticion sin autenticar: CreateTenantUseCase guarda el tenant, eso
| dispara TenantCreated, y el pipeline de TenancyServiceProvider corre CreateDatabase +
| MigrateDatabase con shouldBeQueued(false) — es decir, una base de datos MySQL nueva y toda
| la tanda de migraciones DENTRO de la propia peticion, antes de que nadie apruebe nada.
| Sin tope eso llena el disco y ademas retiene un worker por peticion.
|
| throttle:altas ya existia (3/hora por IP) y dice justo esto. Crear una tienda es un acto
| deliberado y raro: tres por hora sobra para el comerciante honesto.
*/
Route::get('/create/account', [ViewCreateAccountTenantGETController::class, 'index'])->name('central.web.signup.create.account.tenant');
Route::post('/create/account', [CreateAccountTenantPOSTController::class, 'index'])->middleware('throttle:altas');

/*
|--------------------------------------------------------------------------
| Pantallas del propietario de tienda (hallazgo P1)
|--------------------------------------------------------------------------
|
| Llevaban solo 'auth', y sus controladores pasan el `{user_uuid}` de la URL al caso de uso
| sin compararlo nunca con la sesion. Cambiar ese uuid en la barra de direcciones bastaba
| para leer la billetera de otro propietario: ventas brutas, comisiones, saldo disponible y
| el historial de liquidaciones con sus referencias de pago.
|
| El hallazgo A2 ya habia arreglado esto mismo en `/owner/api/*`, que derivan la identidad
| de `auth()->id()`. La correccion no llego a las paginas, que sirven los mismos datos.
|
| `own_user` lo cierra en la ruta, donde se ve al leerla — y donde se vera si falta.
*/
// Rutas del Tenant Owner Central
Route::get('/auth/sso-consume', \Src\Tenant\Infrastructure\Http\Controller\ConsumeTenantOwnerSsoTokenGETController::class)->name('central.tenant.sso-consume');
Route::get('/owner/backoffice/{user_uuid}/dashboard', [ViewDashboardCentralTenantOwnerIndexGETController::class, 'index'])->name('central.backoffice.web.tenant.owner.dashboard')->middleware(['auth', 'own_user']);
Route::get('/owner/backoffice/{user_uuid}/wallet', \Src\Tenant\Infrastructure\Http\Controller\ViewTenantOwnerWalletGETController::class)->name('central.backoffice.web.tenant.owner.wallet')->middleware(['auth', 'own_user']);
Route::get('/owner/backoffice/{user_uuid}/catalog', \Src\Tenant\Infrastructure\Http\Controller\ViewTenantOwnerCentralCatalogGETController::class)->name('central.backoffice.web.tenant.owner.catalog')->middleware(['auth', 'own_user']);
Route::get('/owner/backoffice/{user_uuid}/billing', \Src\Tenant\Infrastructure\Http\Controller\ViewTenantOwnerBillingGETController::class)->name('central.backoffice.web.tenant.owner.billing')->middleware(['auth', 'own_user']);

// A6: el hermano de /create/account. Tiene sesion, pero 'auth' no es un tope: un
// propietario podia crear tiendas —y bases de datos— en bucle igual que un anonimo.
Route::post('/owner/tenant', [CreateTenantPOSTController::class, 'index'])->middleware(['auth', 'throttle:altas']);
Route::delete('/owner/tenant', [DeleteTenantDELETEController::class, 'index'])->middleware('auth');

/*
|--------------------------------------------------------------------------
| APIs del Tenant Owner Central
|--------------------------------------------------------------------------
|
| Estas rutas estaban SIN middleware alguno y tomaban el 'user_id' del cuerpo
| de la petición, de modo que un anónimo podía emitir tokens SSO para entrar
| como cualquier propietario, leer la facturación de cualquier comercio o
| registrar solicitudes de retiro con sus propios datos bancarios (hallazgo A2).
|
| Ahora exigen sesión de propietario, y cada controlador deriva la identidad de
| auth()->id(). La verificación de PROPIEDAD de cada tienda concreta la hace
| Src\Tenant\Application\Service\TenantOwnershipVerifier dentro de los casos de uso.
|
*/
Route::middleware(['auth', 'tenant_owner'])->group(function () {
    Route::post('/owner/filter/tenants', [ConsultTenantByUuidOfOwnerPOSTController::class, 'index']);

    Route::post('/owner/api/sso-token', \Src\Tenant\Infrastructure\Http\Controller\GenerateTenantOwnerSsoTokenPOSTController::class);
    Route::get('/owner/api/wallet-summary', \Src\Tenant\Infrastructure\Http\Controller\GetTenantOwnerWalletSummaryGETController::class);
    Route::post('/owner/api/payout-request', \Src\Tenant\Infrastructure\Http\Controller\CreateTenantOwnerPayoutRequestPOSTController::class);

    /*
     * Hallazgo T3: solicitud de cambio de plan.
     *
     * Antes el boton «Mejorar Plan» de la pantalla de facturacion era un `alert()` que decia
     * «Solicitud registrada. Un asesor te contactara» y no mandaba nada: no existia endpoint
     * ni tabla. El comerciante esperaba una llamada que nadie iba a hacer.
     *
     * Lleva `throttle:altas` (3/hora por IP) por la misma razon que el alta de tiendas:
     * pedir un cambio de plan es un acto deliberado y raro, y sin tope el panel del
     * administrador se llena de solicitudes que no distingue. El caso de uso ademas impide
     * tener mas de una pendiente por tienda.
     */
    Route::post('/owner/api/plan-change-request', \Src\Monetization\Infrastructure\Http\Controller\CreateTenantPlanChangeRequestPOSTController::class)
        ->middleware('throttle:altas');
    Route::get('/owner/api/products', \Src\Tenant\Infrastructure\Http\Controller\ListTenantOwnerProductsGETController::class);
    Route::post('/owner/api/products/{id}/toggle-marketplace', \Src\Tenant\Infrastructure\Http\Controller\ToggleTenantOwnerProductPublicationPOSTController::class);
});
