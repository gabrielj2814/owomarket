<?php

declare(strict_types=1);

namespace Src\Admin\Application\UseCase;

use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Src\User\Infrastructure\Eloquent\Models\User;

final class ListStaffRolesAndPermissionsUseCase
{
    /**
     * @return array{
     *     roles: array<mixed>,
     *     permissions: array<mixed>,
     *     staff_users: array<mixed>,
     *     metrics: array{
     *         total_roles: int,
     *         total_permissions: int,
     *         total_staff: int
     *     }
     * }
     */
    public function execute(): array
    {
        // Asegurar que existan permisos por defecto del sistema
        $this->ensureDefaultPermissionsAndRolesExist();

        $roles = Role::with('permissions')->withCount('users')->get()->map(function ($r) {
            return [
                'id' => $r->id,
                'name' => $r->name,
                'guard_name' => $r->guard_name,
                'permissions' => $r->permissions->pluck('name')->toArray(),
                'users_count' => $r->users_count,
                'created_at' => $r->created_at?->toIso8601String(),
            ];
        })->toArray();

        $permissions = Permission::all()->map(function ($p) {
            return [
                'id' => $p->id,
                'name' => $p->name,
                'guard_name' => $p->guard_name,
            ];
        })->toArray();

        $staffUsers = User::with('roles', 'permissions')->get()->map(function ($u) {
            return [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'type' => $u->type,
                'is_active' => $u->is_active ?? true,
                'roles' => $u->roles->pluck('name')->toArray(),
                'direct_permissions' => $u->permissions->pluck('name')->toArray(),
                'created_at' => $u->created_at?->toIso8601String(),
            ];
        })->toArray();

        return [
            'roles' => $roles,
            'permissions' => $permissions,
            'staff_users' => $staffUsers,
            'metrics' => [
                'total_roles' => count($roles),
                'total_permissions' => count($permissions),
                'total_staff' => count($staffUsers),
            ],
        ];
    }

    private function ensureDefaultPermissionsAndRolesExist(): void
    {
        $defaultPermissions = [
            'manage_tenants' => 'Gestionar tiendas inquilinas y gobernanza',
            'manage_orders' => 'Monitorear órdenes globales y resolver disputas',
            'manage_customers' => 'Directorio central de clientes y bloqueos',
            'manage_payouts' => 'Aprobación de liquidaciones y comprobantes de pago',
            'manage_support' => 'Atención de tickets en mesa central de soporte',
            'manage_catalog' => 'Taxonomía de categorías y marcas maestras',
            'manage_moderation' => 'Moderación y calidad de productos marketplace',
            'manage_cms' => 'Administración de banners y campañas en portada',
            'manage_plans' => 'Configuración de planes de suscripción B2B',
            'manage_staff_roles' => 'Gestión de roles RBAC y permisos de staff',
            'view_audit_logs' => 'Consulta de pista de auditoría de seguridad',
        ];

        foreach ($defaultPermissions as $permName => $desc) {
            Permission::firstOrCreate(['name' => $permName, 'guard_name' => 'web']);
        }

        // Crear Rol Super Administrador con todos los permisos
        $superAdminRole = Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'web']);
        $superAdminRole->syncPermissions(array_keys($defaultPermissions));

        // Rol Soporte Helpdesk
        $supportRole = Role::firstOrCreate(['name' => 'Agente de Soporte', 'guard_name' => 'web']);
        $supportRole->syncPermissions(['manage_support', 'manage_orders', 'manage_customers']);

        // Rol Moderador de Catálogo
        $modRole = Role::firstOrCreate(['name' => 'Moderador de Catálogo', 'guard_name' => 'web']);
        $modRole->syncPermissions(['manage_catalog', 'manage_moderation', 'manage_cms']);

        // Rol Gestor Financiero
        $finRole = Role::firstOrCreate(['name' => 'Gestor Financiero', 'guard_name' => 'web']);
        $finRole->syncPermissions(['manage_payouts', 'manage_plans']);
    }
}
