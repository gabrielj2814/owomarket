<?php

declare(strict_types=1);

namespace Src\Review\Infrastructure\Http\Controller;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Src\Review\Application\UseCases\RespondReviewUseCase;
use Src\Review\Domain\Exceptions\ReviewNotFoundException;
use Src\Review\Infrastructure\Http\Request\RespondReviewFormRequest;

final class RespondReviewPOSTController extends Controller
{
    public function __construct(
        private readonly RespondReviewUseCase $useCase
    ) {}

    public function __invoke(string $id, RespondReviewFormRequest $request): JsonResponse
    {
        try {
            $dto = $request->toDto($id);
            $review = $this->useCase->execute($dto);

            return response()->json([
                'status' => 'success',
                'code' => 200,
                'message' => 'Respuesta registrada con éxito.',
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
                'message' => 'Error al responder la reseña: '.$e->getMessage(),
            ], 500);
        }
    }
}
