<?php

declare(strict_types=1);

namespace Src\Shipment\Infrastructure\Http\Controller;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\JsonResponse;
use Src\Shipment\Application\UseCases\UpdateShipmentTrackingUseCase;
use Src\Shipment\Infrastructure\Http\Request\UpdateShipmentTrackingFormRequest;

final class UpdateShipmentTrackingPOSTController extends Controller
{
    public function __construct(
        private readonly UpdateShipmentTrackingUseCase $updateShipmentTrackingUseCase
    ) {}

    public function __invoke(string $id, UpdateShipmentTrackingFormRequest $request): JsonResponse
    {
        try {
            $dto = $request->toDto();
            $shipment = $this->updateShipmentTrackingUseCase->execute($id, $dto);

            return response()->json([
                'status' => 'success',
                'code' => 200,
                'message' => 'Seguimiento de despacho actualizado exitosamente.',
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
