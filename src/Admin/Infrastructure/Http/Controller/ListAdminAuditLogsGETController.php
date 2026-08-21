<?php

declare(strict_types=1);

namespace Src\Admin\Infrastructure\Http\Controller;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Src\Admin\Application\UseCase\ListCentralAuditLogsUseCase;
use Src\Shared\Helper\ApiResponse;

final class ListAdminAuditLogsGETController
{
    public function __construct(
        private readonly ListCentralAuditLogsUseCase $useCase
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        try {
            $result = $this->useCase->execute([
                'action' => $request->query('action'),
                'entity_type' => $request->query('entity_type'),
                'search' => $request->query('search'),
                'per_page' => (int) ($request->query('per_page', 20)),
                'page' => (int) ($request->query('page', 1)),
            ]);

            return ApiResponse::success(
                data: $result,
                message: 'Registros de auditoría obtenidos exitosamente.'
            );
        } catch (Exception $e) {
            return ApiResponse::error(
                message: $e->getMessage(),
                code: 400
            );
        }
    }
}
