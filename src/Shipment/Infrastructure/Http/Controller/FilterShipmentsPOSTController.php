<?php

declare(strict_types=1);

namespace Src\Shipment\Infrastructure\Http\Controller;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\JsonResponse;
use Src\Shared\Helper\ApiResponse;
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

            return ApiResponse::paginated(
                data: $result->itemsToArray(),
                total: $result->total,
                currentPage: $result->currentPage,
                perPage: $result->perPage,
                lastPage: $result->lastPage,
                message: 'Envíos filtrados exitosamente.'
            );
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
