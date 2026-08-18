<?php

declare(strict_types=1);

namespace Src\Order\Infrastructure\Http\Controller;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\JsonResponse;
use Src\Order\Application\UseCases\UpdateOrderPaymentStatusUseCase;
use Src\Order\Domain\Exceptions\OrderNotFoundException;
use Src\Order\Infrastructure\Http\Request\UpdateOrderPaymentStatusFormRequest;

final class UpdateOrderPaymentStatusPOSTController extends Controller
{
    public function __construct(
        private readonly UpdateOrderPaymentStatusUseCase $updateOrderPaymentStatusUseCase
    ) {}

    public function __invoke(string $id, UpdateOrderPaymentStatusFormRequest $request): JsonResponse
    {
        $paymentStatus = (string) $request->validated('payment_status');

        try {
            $order = $this->updateOrderPaymentStatusUseCase->execute($id, $paymentStatus);

            return response()->json([
                'status' => 'success',
                'code' => 200,
                'message' => "Estado de pago actualizado a '{$paymentStatus}' exitosamente.",
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
