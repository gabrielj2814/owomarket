<?php

declare(strict_types=1);

namespace Src\Order\Infrastructure\Http\Controller;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Src\Order\Application\UseCases\GetOrderMetricsUseCase;

final class GetOrderMetricsGETController extends Controller
{
    public function __construct(
        private readonly GetOrderMetricsUseCase $getOrderMetricsUseCase
    ) {}

    public function __invoke(): JsonResponse
    {
        $metrics = $this->getOrderMetricsUseCase->execute();

        return response()->json([
            'status' => 'success',
            'code' => 200,
            'message' => 'Métricas de pedidos consultadas exitosamente.',
            'data' => $metrics->toArray(),
        ], 200);
    }
}
