<?php

declare(strict_types=1);

namespace Src\Review\Infrastructure\Http\Controller;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Src\Review\Application\UseCases\GetProductRatingSummaryUseCase;

final class GetProductRatingSummaryGETController extends Controller
{
    public function __construct(
        private readonly GetProductRatingSummaryUseCase $useCase
    ) {}

    public function __invoke(?string $productId = null): JsonResponse
    {
        try {
            $summary = $this->useCase->execute($productId);

            return response()->json([
                'status' => 'success',
                'code' => 200,
                'message' => 'Resumen de calificaciones consultado con éxito.',
                'data' => $summary->toArray(),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'error',
                'code' => 500,
                'message' => 'Error al consultar resumen de calificaciones: '.$e->getMessage(),
            ], 500);
        }
    }
}
