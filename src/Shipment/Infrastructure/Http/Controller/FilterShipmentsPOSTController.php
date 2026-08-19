<?php

declare(strict_types=1);

namespace Src\Shipment\Infrastructure\Http\Controller;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\JsonResponse;
use Src\Shipment\Application\UseCases\FilterShipmentsUseCase;
use Src\Shipment\Infrastructure\Http\Request\FilterShipmentsFormRequest;

final class FilterShipmentsPOSTController extends Controller
{
    public function __construct(
        private readonly FilterShipmentsUseCase $filterShipmentsUseCase
    ) {}

    public function __invoke(FilterShipmentsFormRequest $request): JsonResponse
    {
        try {
            $criteria = $request->toDto();
            $result = $this->filterShipmentsUseCase->execute($criteria);

            return response()->json([
                'status' => 'success',
                'code' => 200,
                'message' => 'Envíos filtrados exitosamente.',
                'data' => $result->toArray(),
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
