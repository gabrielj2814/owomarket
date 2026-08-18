<?php

declare(strict_types=1);

namespace Src\Shipment\Infrastructure\Http\Controller;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\JsonResponse;
use Src\Shipment\Application\UseCases\CreateShipmentUseCase;
use Src\Shipment\Infrastructure\Http\Request\CreateShipmentFormRequest;

final class CreateShipmentPOSTController extends Controller
{
    public function __construct(
        private readonly CreateShipmentUseCase $createShipmentUseCase
    ) {}

    public function __invoke(CreateShipmentFormRequest $request): JsonResponse
    {
        try {
            $dto = $request->toDto();
            $shipment = $this->createShipmentUseCase->execute($dto);

            return response()->json([
                'status' => 'success',
                'code' => 201,
                'message' => 'Guía de despacho registrada exitosamente.',
                'data' => $shipment->toArray(),
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
