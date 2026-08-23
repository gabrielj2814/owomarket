<?php

declare(strict_types=1);

namespace Src\Monetization\Infrastructure\Http\Controller;

use Exception;
use Illuminate\Http\JsonResponse;
use Src\Monetization\Application\UseCases\ApproveTenantPlanChangeRequestUseCase;
use Src\Shared\Helper\ApiResponse;

final class ApproveTenantPlanChangeRequestPOSTController
{
    public function __construct(
        private readonly ApproveTenantPlanChangeRequestUseCase $useCase
    ) {}

    public function __invoke(string $id): JsonResponse
    {
        try {
            $solicitud = $this->useCase->execute($id, (string) (auth()->id() ?? 'system'));

            return ApiResponse::success(
                data: ['id' => $solicitud->id, 'status' => $solicitud->status],
                message: 'Cambio de plan aprobado y aplicado.',
                code: 200
            );
        } catch (Exception $e) {
            $codigo = (int) ($e->getCode() ?: 400);

            return ApiResponse::error(
                message: $e->getMessage(),
                code: $codigo >= 400 && $codigo < 600 ? $codigo : 400
            );
        }
    }
}
