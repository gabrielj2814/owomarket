<?php

declare(strict_types=1);

namespace Src\Admin\Application\UseCase;

use App\Models\CentralAuditLog;
use Exception;
use Spatie\Permission\Models\Role;

final class SaveStaffRoleUseCase
{
    /**
     * @param array{
     *     id?: int|string|null,
     *     name: string,
     *     permissions: array<string>
     * } $data
     */
    public function execute(array $data): Role
    {
        $id = $data['id'] ?? null;
        $name = trim($data['name']);
        $permissions = $data['permissions'] ?? [];

        if ($id) {
            $role = Role::findById((int) $id, 'web');
            if (! $role) {
                throw new Exception("Rol con ID '{$id}' no encontrado.", 404);
            }
            $oldPermissions = $role->permissions->pluck('name')->toArray();
            $role->name = $name;
            $role->save();
        } else {
            $role = Role::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
            $oldPermissions = [];
        }

        $role->syncPermissions($permissions);

        CentralAuditLog::log(
            action: $id ? 'role.updated' : 'role.created',
            entityType: 'Role',
            entityId: (string) $role->id,
            description: "Rol '{$role->name}' guardado con " . count($permissions) . " permisos.",
            oldValues: ['permissions' => $oldPermissions],
            newValues: ['permissions' => $permissions]
        );

        return $role;
    }
}
