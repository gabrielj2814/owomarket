<?php

declare(strict_types=1);

namespace Src\Admin\Infrastructure\Http\Controller;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Src\Admin\Application\UseCase\ListProductsForModerationUseCase;
use Src\Shared\Helper\ApiResponse;

final class ListAdminProductsForModerationGETController
{
    public function __construct(
        private readonly ListProductsForModerationUseCase $useCase
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        try {
            $result = $this->useCase->execute([
                'tenant_id' => $request->query('tenant_id'),
                'is_visible' => $request->query('is_visible'),
                'is_featured' => $request->query('is_featured'),
                'search' => $request->query('search'),
                'per_page' => (int) ($request->query('per_page', 15)),
                'page' => (int) ($request->query('page', 1)),
            ]);

            return ApiResponse::success(
                data: $result,
                message: 'Productos para moderación obtenidos exitosamente.'
            );
        } catch (Exception $e) {
            return ApiResponse::error(
                message: $e->getMessage(),
                code: 400
            );
        }
    }
}
