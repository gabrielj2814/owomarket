<?php

declare(strict_types=1);

namespace Src\Admin\Infrastructure\Http\Controller;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Src\Admin\Application\UseCase\ListPendingStorefrontPaymentsUseCase;
use Src\Shared\Helper\ApiResponse;

final class ListPendingStorefrontPaymentsGETController
{
    public function __construct(
        private readonly ListPendingStorefrontPaymentsUseCase $useCase
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        try {
            return ApiResponse::success(
                data: $this->useCase->execute([
                    'tenant_id' => $request->query('tenant_id'),
                    'search' => $request->query('search'),
                    'per_page' => (int) $request->query('per_page', 20),
                    'page' => (int) $request->query('page', 1),
                ]),
                message: 'Cobros pendientes de confirmar.'
            );
        } catch (Exception $e) {
            return ApiResponse::error(message: $e->getMessage(), code: 400);
        }
    }
}
