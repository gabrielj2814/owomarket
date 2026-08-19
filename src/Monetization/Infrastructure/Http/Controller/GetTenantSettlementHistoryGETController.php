<?php

declare(strict_types=1);

namespace Src\Monetization\Infrastructure\Http\Controller;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Src\Monetization\Application\UseCases\GetTenantSettlementHistoryUseCase;
use Src\Shared\Helper\ApiResponse;

final class GetTenantSettlementHistoryGETController
{
    public function __construct(
        private readonly GetTenantSettlementHistoryUseCase $useCase
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $tenantId = tenant('id') ?? (string) $request->input('tenant_id');

        if (! $tenantId) {
            return ApiResponse::error(
                message: 'No se pudo identificar la tienda/inquilino.',
                code: 400
            );
        }

        $settlements = $this->useCase->execute((string) $tenantId);

        return ApiResponse::success(
            data: $settlements,
            message: 'Historial de liquidaciones del inquilino obtenido exitosamente'
        );
    }
}
