<?php

declare(strict_types=1);

namespace Src\Monetization\Infrastructure\Http\Controller;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Src\Monetization\Application\UseCases\GetTenantMonetizationSummaryUseCase;
use Src\Shared\Helper\ApiResponse;

final class GetTenantMonetizationSummaryGETController
{
    public function __construct(
        private readonly GetTenantMonetizationSummaryUseCase $useCase
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

        $summary = $this->useCase->execute((string) $tenantId);

        return ApiResponse::success(
            data: $summary,
            message: 'Resumen de monetización obtenido exitosamente'
        );
    }
}
