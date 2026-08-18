<?php

declare(strict_types=1);

namespace Src\Billing\Infrastructure\Http\Controller;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Src\Billing\Application\UseCases\GetBillingMetricsUseCase;
use Src\Shared\Helper\ApiResponse;

final class GetBillingMetricsGETController extends Controller
{
    public function __construct(
        private readonly GetBillingMetricsUseCase $useCase
    ) {}

    public function __invoke(): JsonResponse
    {
        $metrics = $this->useCase->execute();

        return ApiResponse::success(
            data: $metrics,
            message: 'Métricas de facturación consultadas exitosamente'
        );
    }
}
