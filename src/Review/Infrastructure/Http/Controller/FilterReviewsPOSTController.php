<?php

declare(strict_types=1);

namespace Src\Review\Infrastructure\Http\Controller;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Src\Review\Application\UseCases\FilterReviewsUseCase;
use Src\Review\Infrastructure\Http\Request\FilterReviewsFormRequest;
use Src\Shared\Helper\ApiResponse;

final class FilterReviewsPOSTController extends Controller
{
    public function __construct(
        private readonly FilterReviewsUseCase $useCase
    ) {}

    public function __invoke(FilterReviewsFormRequest $request): JsonResponse
    {
        try {
            $criteria = $request->toDto();
            $result = $this->useCase->execute($criteria);

            return ApiResponse::paginated(
                data: $result->itemsToArray(),
                total: $result->total,
                currentPage: $result->currentPage,
                perPage: $result->perPage,
                lastPage: $result->lastPage,
                message: 'Reseñas consultadas con éxito.'
            );
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'error',
                'code' => 500,
                'message' => 'Error al filtrar las reseñas: '.$e->getMessage(),
            ], 500);
        }
    }
}
