<?php

declare(strict_types=1);

namespace Src\Customer\Infrastructure\Http\Controller;

use Exception;
use Illuminate\Http\JsonResponse;
use Src\Customer\Application\UseCases\GetCustomerMetricsUseCase;

final class GetCustomerMetricsGETController
{
    public function __construct(
        private readonly GetCustomerMetricsUseCase $useCase
    ) {}

    public function __invoke(): JsonResponse
    {
        try {
            $metrics = $this->useCase->execute();

            return response()->json([
                'status' => 'success',
                'data' => $metrics->toArray(),
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Ocurrió un error inesperado al obtener métricas de clientes: '.$e->getMessage(),
            ], 500);
        }
    }
}
