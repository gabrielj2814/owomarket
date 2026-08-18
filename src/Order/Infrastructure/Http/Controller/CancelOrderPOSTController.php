<?php

declare(strict_types=1);

namespace Src\Order\Infrastructure\Http\Controller;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Src\Order\Application\UseCases\CancelOrderUseCase;
use Src\Order\Domain\Exceptions\OrderNotFoundException;

final class CancelOrderPOSTController extends Controller
{
    public function __construct(
        private readonly CancelOrderUseCase $cancelOrderUseCase
    ) {}

    public function __invoke(string $id, Request $request): JsonResponse
    {
        $reason = (string) $request->input('reason', '');

        try {
            $order = $this->cancelOrderUseCase->execute($id, ! empty($reason) ? $reason : null);

            return response()->json([
                'status' => 'success',
                'code' => 200,
                'message' => 'Orden anulada exitosamente.',
                'data' => $order->toArray(),
            ], 200);
        } catch (OrderNotFoundException $e) {
            return response()->json([
                'status' => 'error',
                'code' => 404,
                'message' => $e->getMessage(),
                'data' => null,
            ], 404);
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
