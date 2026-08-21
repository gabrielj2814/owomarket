<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\CentralAuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminPhaseFourOperationsTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;
    private User $staffUser;

    protected function setUp(): void
    {
        parent::setUp();

        \Illuminate\Support\Facades\Event::fake([
            \Stancl\Tenancy\Events\TenantCreated::class,
            \Stancl\Tenancy\Events\TenantDeleted::class,
        ]);

        config([
            'tenancy.bootstrappers' => array_values(array_filter(
                config('tenancy.bootstrappers', []),
                fn ($bootstrapper) => $bootstrapper !== \Stancl\Tenancy\Bootstrappers\DatabaseTenancyBootstrapper::class
            )),
        ]);

        $this->superAdmin = User::create([
            'id' => (string) Str::uuid(),
            'name' => 'Super Admin OwOMarket',
            'email' => 'superadmin.p4@owomarket.local',
            'password' => bcrypt('password123'),
            'type' => 'super_admin',
            'is_active' => true,
        ]);

        $this->staffUser = User::create([
            'id' => (string) Str::uuid(),
            'name' => 'Carlos Soporte',
            'email' => 'carlos.soporte@owomarket.local',
            'password' => bcrypt('password123'),
            'type' => 'staff',
            'is_active' => true,
        ]);
    }

    public function test_super_admin_can_manage_roles_permissions_and_assign_to_staff(): void
    {
        $this->actingAs($this->superAdmin);

        // 1. Renderizar vista de Roles & Staff
        $responseView = $this->get("/admin/backoffice/{$this->superAdmin->id}/security/roles");
        $responseView->assertStatus(200);
        $responseView->assertInertia(fn (Assert $page) => $page
            ->component('admin/security/AdminRolesAndStaffPage')
            ->has('roles')
            ->has('permissions')
            ->has('staff_users')
        );

        // 2. Crear rol personalizado con Spatie Permissions
        $responseCreateRole = $this->postJson('/admin/api/security/roles', [
            'name' => 'Auditor Financiero',
            'permissions' => ['manage_payouts', 'view_audit_logs'],
        ]);

        $responseCreateRole->assertStatus(200);
        $roleId = $responseCreateRole->json('data.id');
        $this->assertNotEmpty($roleId);

        $role = Role::findByName('Auditor Financiero', 'web');
        $this->assertNotNull($role);
        $this->assertTrue($role->hasPermissionTo('manage_payouts'));
        $this->assertTrue($role->hasPermissionTo('view_audit_logs'));

        // 3. Asignar rol al usuario staff
        $responseAssign = $this->postJson("/admin/api/security/staff/{$this->staffUser->id}/roles", [
            'roles' => ['Auditor Financiero'],
        ]);

        $responseAssign->assertStatus(200);
        $this->staffUser->refresh();
        $this->assertTrue($this->staffUser->hasRole('Auditor Financiero'));
        $this->assertTrue($this->staffUser->hasPermissionTo('manage_payouts'));
        $this->assertTrue($this->staffUser->hasPermissionTo('view_audit_logs'));

        // 4. Modificar permisos del rol
        $responseEditRole = $this->postJson('/admin/api/security/roles', [
            'id' => $role->id,
            'name' => 'Auditor Financiero Senior',
            'permissions' => ['manage_payouts', 'manage_plans', 'view_audit_logs'],
        ]);

        $responseEditRole->assertStatus(200);
        $role->refresh();
        $this->assertSame('Auditor Financiero Senior', $role->name);
        $this->assertTrue($role->hasPermissionTo('manage_plans'));
    }

    public function test_super_admin_can_view_and_filter_security_audit_logs(): void
    {
        $this->actingAs($this->superAdmin);

        // Registrar eventos de prueba
        CentralAuditLog::log(
            action: 'tenant.suspended',
            entityType: 'Tenant',
            entityId: 'shop-demo-1',
            description: 'Tienda suspendida por violación de políticas de marketplace.',
            oldValues: ['status' => 'active'],
            newValues: ['status' => 'suspended']
        );

        CentralAuditLog::log(
            action: 'payout.approved',
            entityType: 'CentralPayout',
            entityId: 'payout-123',
            description: 'Liquidación de $450.00 aprobada vía PagoMóvil.',
            oldValues: ['status' => 'pending'],
            newValues: ['status' => 'approved']
        );

        // 1. Renderizar vista de auditoría
        $responseView = $this->get("/admin/backoffice/{$this->superAdmin->id}/security/audit-logs");
        $responseView->assertStatus(200);
        $responseView->assertInertia(fn (Assert $page) => $page
            ->component('admin/security/AdminAuditLogsPage')
            ->has('logs_data.data', 2)
            ->where('metrics.total_logs', 2)
        );

        // 2. Filtrar logs vía API por acción
        $responseFilter = $this->getJson('/admin/api/security/audit-logs?action=tenant.suspended');
        $responseFilter->assertStatus(200);
        $this->assertCount(1, $responseFilter->json('data.logs.data'));
        $this->assertSame('tenant.suspended', $responseFilter->json('data.logs.data.0.action'));
        $this->assertSame('Tenant', $responseFilter->json('data.logs.data.0.entity_type'));
    }
}
