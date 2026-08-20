<?php

declare(strict_types=1);

namespace Src\Tenant\Infrastructure\Http\Controller;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Src\Shared\Helper\ApiResponse;
use Src\Tenant\Application\UseCase\ListTenantOwnerProductsUseCase;

final class ListTenantOwnerProductsGETController
{
    public function __construct(
        private readonly ListTenantOwnerProductsUseCase $useCase
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $userId = (string) ($request->query('user_id') 
            ?: $request->input('user_id') 
            ?: $request->header('X-User-Id') 
            ?: auth()->id());

        $tenantId = $request->query('tenant_id');
        $search = $request->query('search');

        if (empty($userId)) {
            return ApiResponse::error('El parámetro user_id es obligatorio', 400);
        }

        try {
            $result = $this->useCase->execute(
                $userId,
                $tenantId ? (string) $tenantId : null,
                $search ? (string) $search : null
            );

            return ApiResponse::success(
                data: $result,
                message: 'Catálogo de productos obtenido exitosamente',
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
