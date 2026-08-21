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

// module tenant
Route::get('/backoffice/{user_uuid}/module', [ViewModuleTenantIndexGETController::class, 'index'])->name('central.backoffice.web.admin.module.tenant.index')->middleware('auth');
Route::post('/backoffice/filter', [FiltrarTenantsPOSTController::class, 'index'])->middleware('auth');
Route::get('/backoffice/{id}', [ConsultTenantByUuidGETController::class, 'index'])->middleware('auth');
Route::patch('/backoffice/{id}/suspended', [SuspendedTenantByUuidPATCHController::class, 'index'])->middleware('auth');
Route::patch('/backoffice/{id}/active', [ActiveTenantByUuidPATCHController::class, 'index'])->middleware('auth');
Route::patch('/backoffice/{id}/inactive', [InactiveTenantByUuidPATCHController::class, 'index'])->middleware('auth');

// module tenant suspended/inactive
Route::get('/backoffice/{user_uuid}/module/suspended', [ViewModuleTenantSuspendedIndexGETController::class, 'index'])->name('central.backoffice.web.admin.module.tenant.suspended')->middleware('auth');

// module tenant request
Route::get('/backoffice/{user_uuid}/module/request', [ViewModuleTenantRequestIndexGETController::class, 'index'])->name('central.backoffice.web.admin.module.tenant.request')->middleware('auth');
Route::patch('/backoffice/{id}/rejected', [RejectedTenantByUuidPATCHController::class, 'index'])->middleware('auth');
Route::patch('/backoffice/{id}/approved', [ApprovedTenantByUuidPATCHController::class, 'index'])->middleware('auth');

// Expediente 360° del Tenant & Gobernanza Super Admin
Route::get('/backoffice/{user_uuid}/module/tenant/{id}/360', \Src\Tenant\Infrastructure\Http\Controller\ViewAdminTenantDetail360PageGETController::class)->name('central.backoffice.web.admin.module.tenant.360')->middleware('auth');
Route::get('/admin/api/tenants/{id}/360-data', \Src\Tenant\Infrastructure\Http\Controller\GetAdminTenant360DataGETController::class)->middleware('auth');
Route::post('/admin/api/tenants/{id}/sso-token', \Src\Tenant\Infrastructure\Http\Controller\AdminGenerateTenantSsoTokenPOSTController::class)->middleware('auth');
Route::patch('/admin/api/tenants/{id}/governance-status', \Src\Tenant\Infrastructure\Http\Controller\UpdateTenantGovernanceStatusPATCHController::class)->middleware('auth');

Route::get('/create/account', [ViewCreateAccountTenantGETController::class, 'index'])->name('central.web.signup.create.account.tenant');
Route::post('/create/account', [CreateAccountTenantPOSTController::class, 'index']);

// Rutas del Tenant Owner Central
Route::get('/auth/sso-consume', \Src\Tenant\Infrastructure\Http\Controller\ConsumeTenantOwnerSsoTokenGETController::class)->name('central.tenant.sso-consume');
Route::get('/owner/backoffice/{user_uuid}/dashboard', [ViewDashboardCentralTenantOwnerIndexGETController::class, 'index'])->name('central.backoffice.web.tenant.owner.dashboard')->middleware('auth');
Route::get('/owner/backoffice/{user_uuid}/wallet', \Src\Tenant\Infrastructure\Http\Controller\ViewTenantOwnerWalletGETController::class)->name('central.backoffice.web.tenant.owner.wallet')->middleware('auth');
Route::get('/owner/backoffice/{user_uuid}/catalog', \Src\Tenant\Infrastructure\Http\Controller\ViewTenantOwnerCentralCatalogGETController::class)->name('central.backoffice.web.tenant.owner.catalog')->middleware('auth');
Route::get('/owner/backoffice/{user_uuid}/billing', \Src\Tenant\Infrastructure\Http\Controller\ViewTenantOwnerBillingGETController::class)->name('central.backoffice.web.tenant.owner.billing')->middleware('auth');

Route::post('/owner/tenant', [CreateTenantPOSTController::class, 'index'])->middleware('auth');
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
    Route::get('/owner/api/products', \Src\Tenant\Infrastructure\Http\Controller\ListTenantOwnerProductsGETController::class);
    Route::post('/owner/api/products/{id}/toggle-marketplace', \Src\Tenant\Infrastructure\Http\Controller\ToggleTenantOwnerProductPublicationPOSTController::class);
});
