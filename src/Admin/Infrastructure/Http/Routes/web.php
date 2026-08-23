<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Src\Admin\Infrastructure\Http\Controller\ApproveCentralPayoutPOSTController;
use Src\Admin\Infrastructure\Http\Controller\ChangePasswordWithPinPUTController;
use Src\Admin\Infrastructure\Http\Controller\ChangeStatuAdminByUuidPATCHController;
use Src\Admin\Infrastructure\Http\Controller\ConsultAdminByUuidGETController;
use Src\Admin\Infrastructure\Http\Controller\CreateAdminPOSTController;
use Src\Admin\Infrastructure\Http\Controller\DeleteAdminByUuidDELETEController;
use Src\Admin\Infrastructure\Http\Controller\FilterAdminsPOSTController;
use Src\Admin\Infrastructure\Http\Controller\ListCentralPayoutsGETController;
use Src\Admin\Infrastructure\Http\Controller\RejectCentralPayoutPOSTController;
use Src\Admin\Infrastructure\Http\Controller\SendSecurityPinPOSTController;
use Src\Admin\Infrastructure\Http\Controller\UpdateAdminProfilePUTController;
use Src\Admin\Infrastructure\Http\Controller\UpdateAdminPUTController;
use Src\Admin\Infrastructure\Http\Controller\UploadAdminAvatarPOSTController;
use Src\Admin\Infrastructure\Http\Controller\ViewAdminPayoutsPageGETController;
use Src\Admin\Infrastructure\Http\Controller\ViewAdminProfileGETController;
use Src\Admin\Infrastructure\Http\Controller\ViewDashboardAdminGETController;
use Src\Admin\Infrastructure\Http\Controller\ViewModuleAdminIndexGETController;
use Src\Admin\Infrastructure\Http\Controller\ViewModuloAdminFormGETController;
use Src\SupportTicket\Infrastructure\Http\Controller\AdminReplySupportTicketPOSTController;
use Src\SupportTicket\Infrastructure\Http\Controller\FilterAdminSupportTicketsPOSTController;
use Src\SupportTicket\Infrastructure\Http\Controller\UpdateAdminSupportTicketStatusPATCHController;
use Src\SupportTicket\Infrastructure\Http\Controller\ViewAdminSupportTicketsPageGETController;

/*
|--------------------------------------------------------------------------
| Backoffice Central (SuperAdmin / Staff de la plataforma)
|--------------------------------------------------------------------------
|
| ATENCIÓN: este archivo se registra ÚNICAMENTE desde routes/web.php, dentro
| del grupo Route::domain(central_domain). No debe montarse en routes/tenant.php.
|
| Todas las rutas exigen 'auth' (identidad) más un middleware de autorización:
|   'super_admin'         → operaciones de gobierno de la plataforma
|   'staff:<permiso>'     → permiso RBAC de spatie/laravel-permission
|
| Los permisos disponibles los define
| ListStaffRolesAndPermissionsUseCase::ensureDefaultPermissionsAndRolesExist().
| Los usuarios con type = 'super_admin' pasan siempre cualquier comprobación 'staff'.
|
*/

// ---------------------------------------------------------------------------
// Dashboard general del backoffice — accesible a cualquier miembro del staff
// ---------------------------------------------------------------------------
Route::middleware(['auth', 'staff'])->group(function () {
    Route::get('/backoffice/{user_uuid}/dashboard', [ViewDashboardAdminGETController::class, 'index'])->name('central.backoffice.web.admin.dashboard');
});

// ---------------------------------------------------------------------------
// Perfil PROPIO del administrador
// ---------------------------------------------------------------------------
//
// Hallazgo P2: el bloque ya decia «propio», pero nada lo obligaba. Los controladores
// tomaban el `{user_uuid}` de la URL sin compararlo con la sesion, asi que cualquiera con
// sesion en el hub central —un `tenant_owner`, sin ir mas lejos— podia leer el nombre, el
// CORREO y el telefono de otro administrador, y cambiarle el nombre o el avatar.
//
// Comprobado contra la aplicacion real antes de arreglarlo: `PUT` ajeno devolvia 200 y el
// nombre cambiaba.
//
// El arreglo del hallazgo A7 llego a `change-password` —que si resuelve con `auth()->id()`—
// y se salto las otras tres del mismo bloque. `own_user` cierra las cuatro de una vez.
Route::middleware(['auth', 'own_user'])->group(function () {
    Route::get('/backoffice/{user_uuid}/profile', [ViewAdminProfileGETController::class, 'index'])->name('central.backoffice.web.admin.profile');
    Route::put('/backoffice/{user_uuid}/profile', [UpdateAdminProfilePUTController::class, 'index']);
    Route::post('/backoffice/{user_uuid}/profile/avatar', [UploadAdminAvatarPOSTController::class, 'index']);
    // Hallazgo A7: el PIN son 6 digitos (un millon de combinaciones) y NO habia ningun
    // limite de tasa, ni en la ruta ni global (`bootstrap/app.php` no invoca
    // `throttleApi()`), asi que el espacio entero se agotaba dentro de la ventana de
    // validez de 15 minutos.
    Route::post('/backoffice/{user_uuid}/profile/send-pin', [SendSecurityPinPOSTController::class, 'index'])
        ->middleware('throttle:5,15');
    Route::put('/backoffice/{user_uuid}/profile/change-password', [ChangePasswordWithPinPUTController::class, 'index'])
        ->middleware('throttle:5,15');
});

// ---------------------------------------------------------------------------
// Gestión de administradores de la plataforma — sólo super administradores
// ---------------------------------------------------------------------------
Route::middleware(['auth', 'super_admin'])->group(function () {
    Route::get('/backoffice/{user_uuid}/module', [ViewModuleAdminIndexGETController::class, 'index'])->name('central.backoffice.web.admin.module.admin');
    Route::get('/backoffice/{user_uuid}/module/record/{record_id?}', [ViewModuloAdminFormGETController::class, 'index'])->name('central.backoffice.web.admin.module.admin.form');

    Route::post('/backoffice/{user_uuid}/admin', [CreateAdminPOSTController::class, 'index']);
    Route::put('/backoffice/{user_uuid}/admin/{uuid}', [UpdateAdminPUTController::class, 'index']);
    Route::get('/backoffice/{uuid}', [ConsultAdminByUuidGETController::class, 'index']);
    Route::post('/backoffice/filter', [FilterAdminsPOSTController::class, 'index']);
    Route::delete('/backoffice/{uuid}', [DeleteAdminByUuidDELETEController::class, 'index']);
    Route::put('/backoffice/{uuid}/change-statu', [ChangeStatuAdminByUuidPATCHController::class, 'index']);
});

// ---------------------------------------------------------------------------
// Finanzas y Payouts
// ---------------------------------------------------------------------------
Route::middleware(['auth', 'staff:manage_payouts'])->group(function () {
    Route::get('/backoffice/{user_uuid}/payouts', [ViewAdminPayoutsPageGETController::class, 'index'])->name('central.backoffice.web.admin.payouts');
    Route::get('/api/payouts', ListCentralPayoutsGETController::class);
    Route::post('/api/payouts/{id}/approve', ApproveCentralPayoutPOSTController::class);
    Route::post('/api/payouts/{id}/reject', RejectCentralPayoutPOSTController::class);

    /*
     * Hallazgo T3: resolucion de solicitudes de cambio de plan.
     *
     * Bajo el mismo permiso que los retiros —`staff:manage_payouts`— porque es la misma
     * clase de decision: cambiar el plan cambia la `commission_rate` de la tienda, o sea lo
     * que la plataforma le cobra por cada venta.
     */
    Route::get('/backoffice/{user_uuid}/plan-changes', [\Src\Monetization\Infrastructure\Http\Controller\ViewAdminPlanChangesPageGETController::class, 'index'])->name('central.backoffice.web.admin.plan-changes');
    Route::get('/api/plan-changes', \Src\Monetization\Infrastructure\Http\Controller\ListTenantPlanChangeRequestsGETController::class);
    Route::post('/api/plan-changes/{id}/approve', \Src\Monetization\Infrastructure\Http\Controller\ApproveTenantPlanChangeRequestPOSTController::class);
    Route::post('/api/plan-changes/{id}/reject', \Src\Monetization\Infrastructure\Http\Controller\RejectTenantPlanChangeRequestPOSTController::class);
});

// ---------------------------------------------------------------------------
// Mesa Central de Soporte y Tickets
// ---------------------------------------------------------------------------
Route::middleware(['auth', 'staff:manage_support'])->group(function () {
    Route::get('/backoffice/{user_uuid}/support', [ViewAdminSupportTicketsPageGETController::class, 'index'])->name('central.backoffice.web.admin.support');
    Route::post('/api/support/tickets/filter', FilterAdminSupportTicketsPOSTController::class);
    Route::post('/api/support/tickets/{id}/reply', AdminReplySupportTicketPOSTController::class);
    Route::patch('/api/support/tickets/{id}/status', UpdateAdminSupportTicketStatusPATCHController::class);
});

// ---------------------------------------------------------------------------
// Directorio Central de Clientes
// ---------------------------------------------------------------------------
Route::middleware(['auth', 'staff:manage_customers'])->group(function () {
    Route::get('/backoffice/{user_uuid}/customers', \Src\Admin\Infrastructure\Http\Controller\ViewAdminCustomersPageGETController::class)->name('central.backoffice.web.admin.customers');
    Route::get('/api/customers', \Src\Admin\Infrastructure\Http\Controller\ListAdminCustomersGETController::class);
    Route::get('/api/customers/{id}/detail', \Src\Admin\Infrastructure\Http\Controller\GetAdminCustomerDetailGETController::class);
    Route::patch('/api/customers/{id}/toggle-status', \Src\Admin\Infrastructure\Http\Controller\ToggleAdminCustomerStatusPATCHController::class);
});

// ---------------------------------------------------------------------------
// Monitor Global de Órdenes & Disputas
// ---------------------------------------------------------------------------
Route::middleware(['auth', 'staff:manage_orders'])->group(function () {
    Route::get('/backoffice/{user_uuid}/orders', \Src\Admin\Infrastructure\Http\Controller\ViewAdminGlobalOrdersPageGETController::class)->name('central.backoffice.web.admin.orders');
    Route::get('/api/orders', \Src\Admin\Infrastructure\Http\Controller\ListAdminGlobalOrdersGETController::class);
    Route::get('/api/orders/{id}/detail', \Src\Admin\Infrastructure\Http\Controller\GetAdminGlobalOrderDetailGETController::class);
    Route::post('/api/orders/{id}/resolve-dispute', \Src\Admin\Infrastructure\Http\Controller\ResolveAdminOrderDisputePOSTController::class);
});

// ---------------------------------------------------------------------------
// Expediente 360° y Gobernanza de Tenants
// ---------------------------------------------------------------------------
Route::middleware(['auth', 'staff:manage_tenants'])->group(function () {
    Route::get('/api/tenants/{id}/360-data', \Src\Tenant\Infrastructure\Http\Controller\GetAdminTenant360DataGETController::class);
    Route::patch('/api/tenants/{id}/governance-status', \Src\Tenant\Infrastructure\Http\Controller\UpdateTenantGovernanceStatusPATCHController::class);
});

// La impersonación de una tienda es una operación de máximo privilegio: emite un token
// que abre sesión como el propietario. Se reserva al super administrador (hallazgo A9).
Route::middleware(['auth', 'super_admin'])->group(function () {
    Route::post('/api/tenants/{id}/sso-token', \Src\Tenant\Infrastructure\Http\Controller\AdminGenerateTenantSsoTokenPOSTController::class);
});

// ---------------------------------------------------------------------------
// Catálogo Maestro: Marcas y Categorías
// ---------------------------------------------------------------------------
Route::middleware(['auth', 'staff:manage_catalog'])->group(function () {
    Route::get('/backoffice/{user_uuid}/catalog/master-brands', \Src\Admin\Infrastructure\Http\Controller\ViewAdminMasterBrandsPageGETController::class)->name('central.backoffice.web.admin.master_brands');
    Route::get('/api/catalog/master-brands', \Src\Admin\Infrastructure\Http\Controller\ListAdminMasterBrandsGETController::class);
    Route::post('/api/catalog/master-brands', \Src\Admin\Infrastructure\Http\Controller\SaveAdminMasterBrandPOSTController::class);
    Route::delete('/api/catalog/master-brands/{id}', \Src\Admin\Infrastructure\Http\Controller\DeleteAdminMasterBrandDELETEController::class);

    Route::get('/backoffice/{user_uuid}/catalog/master-categories', \Src\Admin\Infrastructure\Http\Controller\ViewAdminMasterCategoriesPageGETController::class)->name('central.backoffice.web.admin.master_categories');
    Route::get('/api/catalog/master-categories', \Src\Admin\Infrastructure\Http\Controller\ListAdminMasterCategoriesGETController::class);
    Route::post('/api/catalog/master-categories', \Src\Admin\Infrastructure\Http\Controller\SaveAdminMasterCategoryPOSTController::class);
    Route::delete('/api/catalog/master-categories/{id}', \Src\Admin\Infrastructure\Http\Controller\DeleteAdminMasterCategoryDELETEController::class);
});

// ---------------------------------------------------------------------------
// Moderación de Productos Marketplace
// ---------------------------------------------------------------------------
Route::middleware(['auth', 'staff:manage_moderation'])->group(function () {
    Route::get('/backoffice/{user_uuid}/catalog/moderation', \Src\Admin\Infrastructure\Http\Controller\ViewAdminProductsModerationPageGETController::class)->name('central.backoffice.web.admin.products_moderation');
    Route::get('/api/catalog/moderation-products', \Src\Admin\Infrastructure\Http\Controller\ListAdminProductsForModerationGETController::class);
    Route::post('/api/catalog/moderation-products/{id}/moderate', \Src\Admin\Infrastructure\Http\Controller\ModerateAdminProductPOSTController::class);
});

// ---------------------------------------------------------------------------
// CMS de Banners de la Home Central
// ---------------------------------------------------------------------------
Route::middleware(['auth', 'staff:manage_cms'])->group(function () {
    Route::get('/backoffice/{user_uuid}/cms/banners', \Src\Admin\Infrastructure\Http\Controller\ViewAdminHomeBannersPageGETController::class)->name('central.backoffice.web.admin.home_banners');
    Route::get('/api/cms/home-banners', \Src\Admin\Infrastructure\Http\Controller\ListAdminHomeBannersGETController::class);
    Route::post('/api/cms/home-banners', \Src\Admin\Infrastructure\Http\Controller\SaveAdminHomeBannerPOSTController::class);
    Route::delete('/api/cms/home-banners/{id}', \Src\Admin\Infrastructure\Http\Controller\DeleteAdminHomeBannerDELETEController::class);
});

// ---------------------------------------------------------------------------
// Planes de Suscripción B2B
// ---------------------------------------------------------------------------
Route::middleware(['auth', 'staff:manage_plans'])->group(function () {
    Route::get('/backoffice/{user_uuid}/plans', \Src\Admin\Infrastructure\Http\Controller\ViewAdminSubscriptionPlansPageGETController::class)->name('central.backoffice.web.admin.subscription_plans');
    Route::get('/api/plans/subscription-plans', \Src\Admin\Infrastructure\Http\Controller\ListAdminSubscriptionPlansGETController::class);
    Route::post('/api/plans/subscription-plans', \Src\Admin\Infrastructure\Http\Controller\SaveAdminSubscriptionPlanPOSTController::class);
    Route::delete('/api/plans/subscription-plans/{id}', \Src\Admin\Infrastructure\Http\Controller\DeleteAdminSubscriptionPlanDELETEController::class);
});

// ---------------------------------------------------------------------------
// Seguridad & Roles RBAC — quien puede asignar roles puede escalar privilegios,
// así que se reserva al super administrador (hallazgo A1).
// ---------------------------------------------------------------------------
Route::middleware(['auth', 'super_admin'])->group(function () {
    Route::get('/backoffice/{user_uuid}/security/roles', \Src\Admin\Infrastructure\Http\Controller\ViewAdminRolesAndStaffPageGETController::class)->name('central.backoffice.web.admin.roles_staff');
    Route::get('/api/security/roles', \Src\Admin\Infrastructure\Http\Controller\ListAdminRolesAndPermissionsGETController::class);
    Route::post('/api/security/roles', \Src\Admin\Infrastructure\Http\Controller\SaveAdminStaffRolePOSTController::class);
    Route::post('/api/security/staff/{userId}/roles', \Src\Admin\Infrastructure\Http\Controller\AssignAdminUserRolesPOSTController::class);
});

// ---------------------------------------------------------------------------
// Pista de Auditoría
// ---------------------------------------------------------------------------
Route::middleware(['auth', 'staff:view_audit_logs'])->group(function () {
    Route::get('/backoffice/{user_uuid}/security/audit-logs', \Src\Admin\Infrastructure\Http\Controller\ViewAdminAuditLogsPageGETController::class)->name('central.backoffice.web.admin.audit_logs');
    Route::get('/api/security/audit-logs', \Src\Admin\Infrastructure\Http\Controller\ListAdminAuditLogsGETController::class);
});
