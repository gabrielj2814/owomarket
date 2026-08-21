<?php

declare(strict_types=1);

namespace Src\Admin\Infrastructure\Http\Controller;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Src\Admin\Application\UseCase\SaveStaffRoleUseCase;
use Src\Shared\Helper\ApiResponse;

final class SaveAdminStaffRolePOSTController
{
    public function __construct(
        private readonly SaveStaffRoleUseCase $useCase
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $request->validate([
            'id' => 'nullable|integer',
            'name' => 'required|string|max:80',
            'permissions' => 'required|array',
            'permissions.*' => 'string|exists:permissions,name',
        ]);

        try {
            $role = $this->useCase->execute([
                'id' => $request->input('id'),
                'name' => $request->input('name'),
                'permissions' => $request->input('permissions', []),
            ]);

            return ApiResponse::success(
                data: $role,
                message: "Rol '{$role->name}' guardado exitosamente."
            );
        } catch (Exception $e) {
            return ApiResponse::error(
                message: $e->getMessage(),
                code: 400
            );
        }
    }
}
