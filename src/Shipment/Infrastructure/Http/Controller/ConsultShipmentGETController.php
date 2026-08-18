<?php

declare(strict_types=1);

namespace Src\Shipment\Infrastructure\Http\Controller;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\JsonResponse;
use Src\Shipment\Application\UseCases\ConsultShipmentByIdUseCase;

final class ConsultShipmentGETController extends Controller
{
    public function __construct(
        private readonly ConsultShipmentByIdUseCase $consultShipmentByIdUseCase
    ) {}

    public function __invoke(string $id): JsonResponse
    {
        try {
            $shipment = $this->consultShipmentByIdUseCase->execute($id);

            return response()->json([
                'status' => 'success',
                'code' => 200,
                'message' => 'Envío consultado exitosamente.',
                'data' => $shipment->toArray(),
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'code' => 404,
                'message' => $e->getMessage(),
                'data' => null,
            ], 404);
        }
    }
}
