<?php

declare(strict_types=1);

namespace Src\Admin\Infrastructure\Http\Controller;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Src\Admin\Application\UseCase\ListCentralOrdersForAdminUseCase;
use Src\Shared\Helper\ApiResponse;

final class ListAdminGlobalOrdersGETController
{
    public function __construct(
        private readonly ListCentralOrdersForAdminUseCase $useCase
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        try {
            $result = $this->useCase->execute([
                'tenant_id' => $request->query('tenant_id'),
                'status' => $request->query('status'),
                'payment_status' => $request->query('payment_status'),
                'search' => $request->query('search'),
                'date_from' => $request->query('date_from'),
                'date_to' => $request->query('date_to'),
                'per_page' => (int) ($request->query('per_page', 15)),
                'page' => (int) ($request->query('page', 1)),
            ]);

            return ApiResponse::success(
                data: $result,
                message: 'Listado global de órdenes obtenido exitosamente.'
            );
        } catch (Exception $e) {
            return ApiResponse::error(
                message: $e->getMessage(),
                code: 400
            );
        }
    }
}
