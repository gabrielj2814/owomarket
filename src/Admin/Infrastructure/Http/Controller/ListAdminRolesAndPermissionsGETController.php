<?php

declare(strict_types=1);

namespace Src\Admin\Infrastructure\Http\Controller;

use Exception;
use Illuminate\Http\JsonResponse;
use Src\Admin\Application\UseCase\ListStaffRolesAndPermissionsUseCase;
use Src\Shared\Helper\ApiResponse;

final class ListAdminRolesAndPermissionsGETController
{
    public function __construct(
        private readonly ListStaffRolesAndPermissionsUseCase $useCase
    ) {}

    public function __invoke(): JsonResponse
    {
        try {
            $result = $this->useCase->execute();

            return ApiResponse::success(
                data: $result,
                message: 'Roles y permisos RBAC obtenidos exitosamente.'
            );
        } catch (Exception $e) {
            return ApiResponse::error(
                message: $e->getMessage(),
                code: 400
            );
        }
    }
}
