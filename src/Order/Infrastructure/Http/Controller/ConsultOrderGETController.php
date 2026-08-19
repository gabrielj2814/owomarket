<?php

declare(strict_types=1);

namespace Src\Order\Infrastructure\Http\Controller;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Src\Order\Application\UseCases\ConsultOrderByIdUseCase;
use Src\Order\Domain\Exceptions\OrderNotFoundException;

final class ConsultOrderGETController extends Controller
{
    public function __construct(
        private readonly ConsultOrderByIdUseCase $consultOrderByIdUseCase
    ) {}

    public function __invoke(string $id): JsonResponse
    {
        try {
            $order = $this->consultOrderByIdUseCase->execute($id);

            return response()->json([
                'status' => 'success',
                'code' => 200,
                'message' => 'Orden consultada exitosamente.',
                'data' => $order->toArray(),
            ], 200);
        } catch (OrderNotFoundException $e) {
            return response()->json([
                'status' => 'error',
                'code' => 404,
                'message' => $e->getMessage(),
                'data' => null,
            ], 404);
        }
    }
}
