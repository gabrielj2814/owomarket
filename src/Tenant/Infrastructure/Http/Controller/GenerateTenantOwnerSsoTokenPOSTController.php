<?php

declare(strict_types=1);

namespace Src\Tenant\Infrastructure\Http\Controller;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Src\Shared\Helper\ApiResponse;
use Src\Tenant\Application\UseCase\GenerateTenantOwnerSsoTokenUseCase;

final class GenerateTenantOwnerSsoTokenPOSTController
{
    public function __construct(
        private readonly GenerateTenantOwnerSsoTokenUseCase $useCase
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $request->validate([
            'tenant_id' => 'required|string',
            'user_id' => 'required|string',
        ]);

        try {
            $result = $this->useCase->execute(
                (string) $request->input('user_id'),
                (string) $request->input('tenant_id')
            );

            return ApiResponse::success(
                data: $result,
                message: 'Token SSO generado exitosamente',
                code: 200
            );
        } catch (Exception $e) {
            return ApiResponse::error(
                message: $e->getMessage(),
                code: (int) ($e->getCode() ?: 400)
            );
        }
    }
}
