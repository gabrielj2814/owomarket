<?php

declare(strict_types=1);

namespace Src\Order\Infrastructure\Http\Controller;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Src\Order\Application\UseCases\ConsultOrderByOrderNumberUseCase;
use Src\Order\Domain\Exceptions\OrderNotFoundException;

final class ConsultOrderByOrderNumberGETController extends Controller
{
    public function __construct(
        private readonly ConsultOrderByOrderNumberUseCase $consultOrderByOrderNumberUseCase
    ) {}

    public function __invoke(string $orderNumber): JsonResponse
    {
        try {
            $order = $this->consultOrderByOrderNumberUseCase->execute($orderNumber);

            return response()->json([
                'status' => 'success',
                'code' => 200,
                'message' => 'Orden consultada exitosamente por número.',
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
