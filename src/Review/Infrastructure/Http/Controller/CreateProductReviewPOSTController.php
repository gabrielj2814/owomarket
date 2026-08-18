<?php

declare(strict_types=1);

namespace Src\Review\Infrastructure\Http\Controller;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Src\Review\Application\UseCases\CreateProductReviewUseCase;
use Src\Review\Domain\Exceptions\DuplicateReviewException;
use Src\Review\Domain\Exceptions\InvalidRatingException;
use Src\Review\Infrastructure\Http\Request\CreateProductReviewFormRequest;

final class CreateProductReviewPOSTController extends Controller
{
    public function __construct(
        private readonly CreateProductReviewUseCase $useCase
    ) {}

    public function __invoke(CreateProductReviewFormRequest $request): JsonResponse
    {
        try {
            $dto = $request->toDto();
            $review = $this->useCase->execute($dto);

            return response()->json([
                'status' => 'success',
                'code' => 201,
                'message' => 'Reseña registrada con éxito.',
                'data' => $review->toArray(),
            ], 201);
        } catch (DuplicateReviewException $e) {
            return response()->json([
                'status' => 'error',
                'code' => 409,
                'message' => $e->getMessage(),
            ], 409);
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
                'message' => 'Error interno al registrar la reseña: '.$e->getMessage(),
            ], 500);
        }
    }
}
