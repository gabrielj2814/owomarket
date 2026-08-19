<?php

declare(strict_types=1);

namespace Src\Review\Infrastructure\Http\Controller;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Src\Review\Application\UseCases\ConsultReviewByIdUseCase;
use Src\Review\Domain\Exceptions\ReviewNotFoundException;

final class ConsultReviewGETController extends Controller
{
    public function __construct(
        private readonly ConsultReviewByIdUseCase $useCase
    ) {}

    public function __invoke(string $id): JsonResponse
    {
        try {
            $review = $this->useCase->execute($id);

            return response()->json([
                'status' => 'success',
                'code' => 200,
                'message' => 'Reseña consultada con éxito.',
                'data' => $review->toArray(),
            ]);
        } catch (ReviewNotFoundException $e) {
            return response()->json([
                'status' => 'error',
                'code' => 404,
                'message' => $e->getMessage(),
            ], 404);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'error',
                'code' => 500,
                'message' => 'Error al consultar la reseña: '.$e->getMessage(),
            ], 500);
        }
    }
}
