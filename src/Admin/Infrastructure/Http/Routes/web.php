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

Route::get('/backoffice/{user_uuid}/dashboard', [ViewDashboardAdminGETController::class, 'index'])->name('central.backoffice.web.admin.dashboard')->middleware('auth');
Route::get('/backoffice/{user_uuid}/module', [ViewModuleAdminIndexGETController::class, 'index'])->name('central.backoffice.web.admin.module.admin')->middleware('auth');
Route::get('/backoffice/{user_uuid}/module/record/{record_id?}', [ViewModuloAdminFormGETController::class, 'index'])->name('central.backoffice.web.admin.module.admin.form')->middleware('auth');

// Rutas de Perfil Administrativo
Route::get('/backoffice/{user_uuid}/profile', [ViewAdminProfileGETController::class, 'index'])->name('central.backoffice.web.admin.profile')->middleware('auth');
Route::put('/backoffice/{user_uuid}/profile', [UpdateAdminProfilePUTController::class, 'index'])->middleware('auth');
Route::post('/backoffice/{user_uuid}/profile/avatar', [UploadAdminAvatarPOSTController::class, 'index'])->middleware('auth');
Route::post('/backoffice/{user_uuid}/profile/send-pin', [SendSecurityPinPOSTController::class, 'index'])->middleware('auth');
Route::put('/backoffice/{user_uuid}/profile/change-password', [ChangePasswordWithPinPUTController::class, 'index'])->middleware('auth');

Route::post('/backoffice/{user_uuid}/admin', [CreateAdminPOSTController::class, 'index'])->middleware('auth');
Route::put('/backoffice/{user_uuid}/admin/{uuid}', [UpdateAdminPUTController::class, 'index'])->middleware('auth');
Route::get('/backoffice/{uuid}', [ConsultAdminByUuidGETController::class, 'index'])->middleware('auth');
Route::post('/backoffice/filter', [FilterAdminsPOSTController::class, 'index'])->middleware('auth');
Route::delete('/backoffice/{uuid}', [DeleteAdminByUuidDELETEController::class, 'index'])->middleware('auth');
Route::put('/backoffice/{uuid}/change-statu', [ChangeStatuAdminByUuidPATCHController::class, 'index'])->middleware('auth');

// Rutas de Finanzas y Payouts
Route::get('/backoffice/{user_uuid}/payouts', [ViewAdminPayoutsPageGETController::class, 'index'])->name('central.backoffice.web.admin.payouts')->middleware('auth');
Route::get('/api/payouts', ListCentralPayoutsGETController::class)->middleware('auth');
Route::post('/api/payouts/{id}/approve', ApproveCentralPayoutPOSTController::class)->middleware('auth');
Route::post('/api/payouts/{id}/reject', RejectCentralPayoutPOSTController::class)->middleware('auth');

// Rutas de Mesa Central de Soporte y Tickets
Route::get('/backoffice/{user_uuid}/support', [ViewAdminSupportTicketsPageGETController::class, 'index'])->name('central.backoffice.web.admin.support')->middleware('auth');
Route::post('/api/support/tickets/filter', FilterAdminSupportTicketsPOSTController::class)->middleware('auth');
Route::post('/api/support/tickets/{id}/reply', AdminReplySupportTicketPOSTController::class)->middleware('auth');
Route::patch('/api/support/tickets/{id}/status', UpdateAdminSupportTicketStatusPATCHController::class)->middleware('auth');

// Rutas del Directorio Central de Clientes
Route::get('/backoffice/{user_uuid}/customers', \Src\Admin\Infrastructure\Http\Controller\ViewAdminCustomersPageGETController::class)->name('central.backoffice.web.admin.customers')->middleware('auth');
Route::get('/api/customers', \Src\Admin\Infrastructure\Http\Controller\ListAdminCustomersGETController::class)->middleware('auth');
Route::get('/api/customers/{id}/detail', \Src\Admin\Infrastructure\Http\Controller\GetAdminCustomerDetailGETController::class)->middleware('auth');
Route::patch('/api/customers/{id}/toggle-status', \Src\Admin\Infrastructure\Http\Controller\ToggleAdminCustomerStatusPATCHController::class)->middleware('auth');

// Rutas del Monitor Global de Órdenes & Disputas
Route::get('/backoffice/{user_uuid}/orders', \Src\Admin\Infrastructure\Http\Controller\ViewAdminGlobalOrdersPageGETController::class)->name('central.backoffice.web.admin.orders')->middleware('auth');
Route::get('/api/orders', \Src\Admin\Infrastructure\Http\Controller\ListAdminGlobalOrdersGETController::class)->middleware('auth');
Route::get('/api/orders/{id}/detail', \Src\Admin\Infrastructure\Http\Controller\GetAdminGlobalOrderDetailGETController::class)->middleware('auth');
Route::post('/api/orders/{id}/resolve-dispute', \Src\Admin\Infrastructure\Http\Controller\ResolveAdminOrderDisputePOSTController::class)->middleware('auth');

// Rutas de API Expediente 360° y Gobernanza de Tenants
Route::get('/api/tenants/{id}/360-data', \Src\Tenant\Infrastructure\Http\Controller\GetAdminTenant360DataGETController::class)->middleware('auth');
Route::post('/api/tenants/{id}/sso-token', \Src\Tenant\Infrastructure\Http\Controller\AdminGenerateTenantSsoTokenPOSTController::class)->middleware('auth');
Route::patch('/api/tenants/{id}/governance-status', \Src\Tenant\Infrastructure\Http\Controller\UpdateTenantGovernanceStatusPATCHController::class)->middleware('auth');

// Rutas de Catálogo Maestro: Marcas
Route::get('/backoffice/{user_uuid}/catalog/master-brands', \Src\Admin\Infrastructure\Http\Controller\ViewAdminMasterBrandsPageGETController::class)->name('central.backoffice.web.admin.master_brands')->middleware('auth');
Route::get('/api/catalog/master-brands', \Src\Admin\Infrastructure\Http\Controller\ListAdminMasterBrandsGETController::class)->middleware('auth');
Route::post('/api/catalog/master-brands', \Src\Admin\Infrastructure\Http\Controller\SaveAdminMasterBrandPOSTController::class)->middleware('auth');
Route::delete('/api/catalog/master-brands/{id}', \Src\Admin\Infrastructure\Http\Controller\DeleteAdminMasterBrandDELETEController::class)->middleware('auth');

// Rutas de Catálogo Maestro: Categorías
Route::get('/backoffice/{user_uuid}/catalog/master-categories', \Src\Admin\Infrastructure\Http\Controller\ViewAdminMasterCategoriesPageGETController::class)->name('central.backoffice.web.admin.master_categories')->middleware('auth');
Route::get('/api/catalog/master-categories', \Src\Admin\Infrastructure\Http\Controller\ListAdminMasterCategoriesGETController::class)->middleware('auth');
Route::post('/api/catalog/master-categories', \Src\Admin\Infrastructure\Http\Controller\SaveAdminMasterCategoryPOSTController::class)->middleware('auth');
Route::delete('/api/catalog/master-categories/{id}', \Src\Admin\Infrastructure\Http\Controller\DeleteAdminMasterCategoryDELETEController::class)->middleware('auth');

// Rutas de Moderación de Productos Marketplace
Route::get('/backoffice/{user_uuid}/catalog/moderation', \Src\Admin\Infrastructure\Http\Controller\ViewAdminProductsModerationPageGETController::class)->name('central.backoffice.web.admin.products_moderation')->middleware('auth');
Route::get('/api/catalog/moderation-products', \Src\Admin\Infrastructure\Http\Controller\ListAdminProductsForModerationGETController::class)->middleware('auth');
Route::post('/api/catalog/moderation-products/{id}/moderate', \Src\Admin\Infrastructure\Http\Controller\ModerateAdminProductPOSTController::class)->middleware('auth');

// Rutas de CMS de Banners de la Home Central
Route::get('/backoffice/{user_uuid}/cms/banners', \Src\Admin\Infrastructure\Http\Controller\ViewAdminHomeBannersPageGETController::class)->name('central.backoffice.web.admin.home_banners')->middleware('auth');
Route::get('/api/cms/home-banners', \Src\Admin\Infrastructure\Http\Controller\ListAdminHomeBannersGETController::class)->middleware('auth');
Route::post('/api/cms/home-banners', \Src\Admin\Infrastructure\Http\Controller\SaveAdminHomeBannerPOSTController::class)->middleware('auth');
Route::delete('/api/cms/home-banners/{id}', \Src\Admin\Infrastructure\Http\Controller\DeleteAdminHomeBannerDELETEController::class)->middleware('auth');

// Rutas de Planes de Suscripción B2B
Route::get('/backoffice/{user_uuid}/plans', \Src\Admin\Infrastructure\Http\Controller\ViewAdminSubscriptionPlansPageGETController::class)->name('central.backoffice.web.admin.subscription_plans')->middleware('auth');
Route::get('/api/plans/subscription-plans', \Src\Admin\Infrastructure\Http\Controller\ListAdminSubscriptionPlansGETController::class)->middleware('auth');
Route::post('/api/plans/subscription-plans', \Src\Admin\Infrastructure\Http\Controller\SaveAdminSubscriptionPlanPOSTController::class)->middleware('auth');
Route::delete('/api/plans/subscription-plans/{id}', \Src\Admin\Infrastructure\Http\Controller\DeleteAdminSubscriptionPlanDELETEController::class)->middleware('auth');

// Rutas de Seguridad & Roles RBAC (Spatie Permission)
Route::get('/backoffice/{user_uuid}/security/roles', \Src\Admin\Infrastructure\Http\Controller\ViewAdminRolesAndStaffPageGETController::class)->name('central.backoffice.web.admin.roles_staff')->middleware('auth');
Route::get('/api/security/roles', \Src\Admin\Infrastructure\Http\Controller\ListAdminRolesAndPermissionsGETController::class)->middleware('auth');
Route::post('/api/security/roles', \Src\Admin\Infrastructure\Http\Controller\SaveAdminStaffRolePOSTController::class)->middleware('auth');
Route::post('/api/security/staff/{userId}/roles', \Src\Admin\Infrastructure\Http\Controller\AssignAdminUserRolesPOSTController::class)->middleware('auth');

// Rutas de Pista de Auditoría
Route::get('/backoffice/{user_uuid}/security/audit-logs', \Src\Admin\Infrastructure\Http\Controller\ViewAdminAuditLogsPageGETController::class)->name('central.backoffice.web.admin.audit_logs')->middleware('auth');
Route::get('/api/security/audit-logs', \Src\Admin\Infrastructure\Http\Controller\ListAdminAuditLogsGETController::class)->middleware('auth');


