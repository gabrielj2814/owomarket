<?php

declare(strict_types=1);

namespace Src\Shipment\Infrastructure\Http\Controller;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Src\Shipment\Application\UseCases\MarkShipmentAsDeliveredUseCase;

final class MarkShipmentAsDeliveredPOSTController extends Controller
{
    public function __construct(
        private readonly MarkShipmentAsDeliveredUseCase $markShipmentAsDeliveredUseCase
    ) {}

    public function __invoke(string $id, Request $request): JsonResponse
    {
        try {
            $deliveredAt = $request->input('delivered_at');
            $shipment = $this->markShipmentAsDeliveredUseCase->execute($id, $deliveredAt);

            return response()->json([
                'status' => 'success',
                'code' => 200,
                'message' => 'Envío marcado como entregado exitosamente.',
                'data' => $shipment->toArray(),
            ], 200);
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
