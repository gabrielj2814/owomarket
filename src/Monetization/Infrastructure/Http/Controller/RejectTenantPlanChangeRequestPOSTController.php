<?php

declare(strict_types=1);

namespace Src\Monetization\Infrastructure\Http\Controller;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Src\Monetization\Application\UseCases\RejectTenantPlanChangeRequestUseCase;
use Src\Shared\Helper\ApiResponse;

final class RejectTenantPlanChangeRequestPOSTController
{
    public function __construct(
        private readonly RejectTenantPlanChangeRequestUseCase $useCase
    ) {}

    public function __invoke(Request $request, string $id): JsonResponse
    {
        $request->validate(['rejection_reason' => 'required|string|max:1000']);

        try {
            $solicitud = $this->useCase->execute(
                $id,
                (string) (auth()->id() ?? 'system'),
                (string) $request->input('rejection_reason')
            );

            return ApiResponse::success(
                data: ['id' => $solicitud->id, 'status' => $solicitud->status],
                message: 'Solicitud de cambio de plan rechazada.',
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
