<?php

declare(strict_types=1);

namespace Src\Shipment\Infrastructure\Http\Controller;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\JsonResponse;
use Src\Shipment\Application\UseCases\ConsultShipmentByOrderIdUseCase;
use Src\Shipment\Domain\Entities\Shipment;

final class ConsultShipmentsByOrderGETController extends Controller
{
    public function __construct(
        private readonly ConsultShipmentByOrderIdUseCase $consultShipmentByOrderIdUseCase
    ) {}

    public function __invoke(string $orderId): JsonResponse
    {
        try {
            $shipments = $this->consultShipmentByOrderIdUseCase->execute($orderId);

            return response()->json([
                'status' => 'success',
                'code' => 200,
                'message' => 'Envíos de la orden consultados exitosamente.',
                'data' => array_map(fn (Shipment $s) => $s->toArray(), $shipments),
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'code' => 400,
                'message' => $e->getMessage(),
                'data' => [],
            ], 400);
        }
    }
}
