<?php

declare(strict_types=1);

namespace Src\Shipment\Infrastructure\Http\Controller;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\JsonResponse;
use Src\Shipment\Application\UseCases\GetShipmentMetricsUseCase;

final class GetShipmentMetricsGETController extends Controller
{
    public function __construct(
        private readonly GetShipmentMetricsUseCase $getShipmentMetricsUseCase
    ) {}

    public function __invoke(): JsonResponse
    {
        try {
            $metrics = $this->getShipmentMetricsUseCase->execute();

            return response()->json([
                'status' => 'success',
                'code' => 200,
                'message' => 'Métricas de despacho consultadas exitosamente.',
                'data' => $metrics->toArray(),
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'code' => 400,
                'message' => $e->getMessage(),
                'data' => null,
            ], 400);
        }
    }
}
