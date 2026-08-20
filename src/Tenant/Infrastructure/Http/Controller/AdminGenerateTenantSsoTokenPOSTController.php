<?php

declare(strict_types=1);

namespace Src\Tenant\Infrastructure\Http\Controller;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Src\Shared\Helper\ApiResponse;
use Src\Tenant\Application\UseCase\AdminImpersonateTenantUseCase;

final class AdminGenerateTenantSsoTokenPOSTController
{
    public function __construct(
        private readonly AdminImpersonateTenantUseCase $impersonateUseCase
    ) {}

    public function __invoke(Request $request, string $id): JsonResponse
    {
        try {
            $user = auth()->user();
            $adminUserId = (string) ($user?->id ?? $request->input('admin_user_id'));

            $result = $this->impersonateUseCase->execute($id, $adminUserId);

            return ApiResponse::success(
                data: $result,
                message: 'Enlace de acceso directo generado exitosamente.'
            );
        } catch (Exception $e) {
            return ApiResponse::error(
                message: $e->getMessage(),
                code: (int) ($e->getCode() ?: 400)
            );
        }
    }
}
