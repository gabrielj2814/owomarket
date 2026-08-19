<?php

declare(strict_types=1);

namespace Src\Order\Infrastructure\Http\Controller;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\JsonResponse;
use Src\Order\Application\UseCases\CancelOrderUseCase;
use Src\Order\Application\UseCases\ConfirmOrderUseCase;
use Src\Order\Application\UseCases\DeliverOrderUseCase;
use Src\Order\Application\UseCases\ProcessOrderUseCase;
use Src\Order\Application\UseCases\RefundOrderUseCase;
use Src\Order\Application\UseCases\ShipOrderUseCase;
use Src\Order\Domain\Exceptions\OrderNotFoundException;
use Src\Order\Infrastructure\Http\Request\UpdateOrderStatusFormRequest;

final class UpdateOrderStatusPOSTController extends Controller
{
    public function __construct(
        private readonly ConfirmOrderUseCase $confirmOrderUseCase,
        private readonly ProcessOrderUseCase $processOrderUseCase,
        private readonly ShipOrderUseCase $shipOrderUseCase,
        private readonly DeliverOrderUseCase $deliverOrderUseCase,
        private readonly CancelOrderUseCase $cancelOrderUseCase,
        private readonly RefundOrderUseCase $refundOrderUseCase
    ) {}

    public function __invoke(string $id, UpdateOrderStatusFormRequest $request): JsonResponse
    {
        $status = (string) $request->validated('status');
        $shippingMethod = $request->validated('shipping_method');
        $reason = $request->validated('reason');

        try {
            $order = match ($status) {
                'confirmed' => $this->confirmOrderUseCase->execute($id),
                'processing' => $this->processOrderUseCase->execute($id),
                'shipped' => $this->shipOrderUseCase->execute($id, $shippingMethod),
                'delivered' => $this->deliverOrderUseCase->execute($id),
                'cancelled' => $this->cancelOrderUseCase->execute($id, $reason),
                'refunded' => $this->refundOrderUseCase->execute($id),
            };

            return response()->json([
                'status' => 'success',
                'code' => 200,
                'message' => "Estado de la orden actualizado a '{$status}' exitosamente.",
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
