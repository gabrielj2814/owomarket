<?php

declare(strict_types=1);

namespace Src\Monetization\Infrastructure\Http\Controller;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Src\Monetization\Application\UseCases\ListTenantPlanChangeRequestsUseCase;
use Src\Shared\Helper\ApiResponse;

final class ListTenantPlanChangeRequestsGETController
{
    public function __construct(
        private readonly ListTenantPlanChangeRequestsUseCase $useCase
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        return ApiResponse::success(
            data: $this->useCase->execute($request->query('status') ? (string) $request->query('status') : null),
            message: 'Solicitudes de cambio de plan recuperadas.',
            code: 200
        );
    }
}
