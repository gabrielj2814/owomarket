<?php

declare(strict_types=1);

namespace Src\Admin\Infrastructure\Http\Controller;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Src\Admin\Application\UseCase\AssignUserRolesUseCase;
use Src\Shared\Helper\ApiResponse;

final class AssignAdminUserRolesPOSTController
{
    public function __construct(
        private readonly AssignUserRolesUseCase $useCase
    ) {}

    public function __invoke(Request $request, string $userId): JsonResponse
    {
        $request->validate([
            'roles' => 'required|array',
            'roles.*' => 'string|exists:roles,name',
            'direct_permissions' => 'nullable|array',
        ]);

        try {
            $user = $this->useCase->execute($userId, [
                'roles' => $request->input('roles', []),
                'direct_permissions' => $request->input('direct_permissions'),
            ]);

            return ApiResponse::success(
                data: $user,
                message: "Roles y permisos asignados a '{$user->name}' exitosamente."
            );
        } catch (Exception $e) {
            return ApiResponse::error(
                message: $e->getMessage(),
                code: 400
            );
        }
    }
}
