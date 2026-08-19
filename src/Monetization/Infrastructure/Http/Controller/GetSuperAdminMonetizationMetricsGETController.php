<?php

declare(strict_types=1);

namespace Src\Monetization\Infrastructure\Http\Controller;

use Illuminate\Http\JsonResponse;
use Src\Monetization\Application\UseCases\GetSuperAdminMonetizationMetricsUseCase;
use Src\Shared\Helper\ApiResponse;

final class GetSuperAdminMonetizationMetricsGETController
{
    public function __construct(
        private readonly GetSuperAdminMonetizationMetricsUseCase $useCase
    ) {}

    public function __invoke(): JsonResponse
    {
        $metrics = $this->useCase->execute();

        return ApiResponse::success(
            data: $metrics,
            message: 'Métricas de monetización global recuperadas exitosamente'
        );
    }
}
