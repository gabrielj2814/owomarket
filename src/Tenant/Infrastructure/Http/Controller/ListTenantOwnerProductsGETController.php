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
        // La identidad SIEMPRE sale de la sesión, nunca de la query string
        // ni de la cabecera X-User-Id (hallazgo A2).
        $userId = (string) (auth()->id() ?? '');

        if ($userId === '') {
            return ApiResponse::error('Debes iniciar sesión para consultar tu catálogo.', 401);
        }

        $tenantId = $request->query('tenant_id');
        $search = $request->query('search');

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
