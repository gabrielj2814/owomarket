<?php

declare(strict_types=1);

namespace Src\Admin\Infrastructure\Http\Controller;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Src\Admin\Application\UseCase\ListCentralCustomersForAdminUseCase;
use Src\Shared\Helper\ApiResponse;

final class ListAdminCustomersGETController
{
    public function __construct(
        private readonly ListCentralCustomersForAdminUseCase $useCase
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        try {
            $result = $this->useCase->execute([
                'search' => $request->query('search'),
                'is_active' => $request->query('is_active'),
                'per_page' => (int) ($request->query('per_page', 15)),
                'page' => (int) ($request->query('page', 1)),
            ]);

            return ApiResponse::success(
                data: $result,
                message: 'Listado de clientes obtenido exitosamente.'
            );
        } catch (Exception $e) {
            return ApiResponse::error(
                message: $e->getMessage(),
                code: 400
            );
        }
    }
}
