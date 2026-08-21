<?php

declare(strict_types=1);

namespace Src\Admin\Application\UseCase;

use App\Models\CentralAuditLog;
use App\Models\User;
use Exception;

final class AssignUserRolesUseCase
{
    /**
     * @param array{
     *     roles: array<string>,
     *     direct_permissions?: array<string>
     * } $data
     */
    public function execute(string $userId, array $data): User
    {
        $user = User::find($userId);

        if (! $user) {
            throw new Exception("Usuario '{$userId}' no encontrado.", 404);
        }

        $oldRoles = $user->roles->pluck('name')->toArray();
        $oldPermissions = $user->permissions->pluck('name')->toArray();

        $roles = $data['roles'] ?? [];
        $user->syncRoles($roles);

        if (isset($data['direct_permissions'])) {
            $user->syncPermissions($data['direct_permissions']);
        }

        CentralAuditLog::log(
            action: 'user_roles.assigned',
            entityType: 'User',
            entityId: $user->id,
            description: "Roles del usuario '{$user->email}' actualizados a [" . implode(', ', $roles) . "].",
            oldValues: ['roles' => $oldRoles, 'permissions' => $oldPermissions],
            newValues: ['roles' => $roles, 'permissions' => $data['direct_permissions'] ?? []]
        );

        return $user;
    }
}
