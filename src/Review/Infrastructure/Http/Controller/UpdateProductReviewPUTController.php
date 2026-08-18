<?php

declare(strict_types=1);

namespace Src\Review\Infrastructure\Http\Controller;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Src\Review\Application\UseCases\UpdateProductReviewUseCase;
use Src\Review\Domain\Exceptions\InvalidRatingException;
use Src\Review\Domain\Exceptions\ReviewNotFoundException;
use Src\Review\Infrastructure\Http\Request\UpdateProductReviewFormRequest;

final class UpdateProductReviewPUTController extends Controller
{
    public function __construct(
        private readonly UpdateProductReviewUseCase $useCase
    ) {}

    public function __invoke(string $id, UpdateProductReviewFormRequest $request): JsonResponse
    {
        try {
            $dto = $request->toDto($id);
            $review = $this->useCase->execute($dto);

            return response()->json([
                'status' => 'success',
                'code' => 200,
                'message' => 'Reseña actualizada con éxito.',
                'data' => $review->toArray(),
            ]);
        } catch (ReviewNotFoundException $e) {
            return response()->json([
                'status' => 'error',
                'code' => 404,
                'message' => $e->getMessage(),
            ], 404);
        } catch (InvalidRatingException $e) {
            return response()->json([
                'status' => 'error',
                'code' => 422,
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'error',
                'code' => 500,
                'message' => 'Error al actualizar la reseña: '.$e->getMessage(),
            ], 500);
        }
    }
}
