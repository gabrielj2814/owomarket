<?php

declare(strict_types=1);

namespace Src\Order\Infrastructure\Http\Controller;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\JsonResponse;
use Src\Order\Application\UseCases\CreateOrderUseCase;
use Src\Order\Infrastructure\Http\Request\CreateOrderFormRequest;

final class CreateOrderPOSTController extends Controller
{
    public function __construct(
        private readonly CreateOrderUseCase $createOrderUseCase
    ) {}

    public function __invoke(CreateOrderFormRequest $request): JsonResponse
    {
        try {
            $dto = $request->toDto();
            $order = $this->createOrderUseCase->execute($dto);

            return response()->json([
                'status' => 'success',
                'code' => 201,
                'message' => 'Orden de venta creada exitosamente.',
                'data' => $order->toArray(),
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'code' => 400,
                'message' => $e->getMessage(),
                'data' => null,
            ], 400);
        }
    }
}
