<?php

declare(strict_types=1);

namespace Src\Review\Infrastructure\Http\Controller;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Src\Review\Application\UseCases\DeleteProductReviewUseCase;
use Src\Review\Domain\Exceptions\ReviewNotFoundException;

final class DeleteProductReviewDELETEController extends Controller
{
    public function __construct(
        private readonly DeleteProductReviewUseCase $useCase
    ) {}

    public function __invoke(string $id): JsonResponse
    {
        try {
            $this->useCase->execute($id);

            return response()->json([
                'status' => 'success',
                'code' => 200,
                'message' => 'Reseña eliminada con éxito.',
                'data' => null,
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
                'message' => 'Error al eliminar la reseña: '.$e->getMessage(),
            ], 500);
        }
    }
}
