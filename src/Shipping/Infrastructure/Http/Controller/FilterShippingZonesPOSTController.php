<?php

declare(strict_types=1);

namespace Src\Shipping\Infrastructure\Http\Controller;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Src\Shared\Helper\ApiResponse;
use Src\Shipping\Application\DTOs\ShippingZoneFilterCriteria;
use Src\Shipping\Application\UseCase\FilterShippingZonesUseCase;

final class FilterShippingZonesPOSTController
{
    public function __construct(
        private readonly FilterShippingZonesUseCase $useCase
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        try {
            $criteria = new ShippingZoneFilterCriteria(
                search: $request->filled('search') ? (string) $request->input('search') : null,
                isActive: $request->has('is_active') && $request->input('is_active') !== ''
                    ? filter_var($request->input('is_active'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE)
                    : null,
                page: (int) $request->input('page', 1),
                perPage: (int) $request->input('prePage', $request->input('perPage', 10)),
                sortBy: (string) $request->input('sortBy', 'priority'),
                sortDirection: (string) $request->input('sortDirection', 'asc')
            );

            $result = $this->useCase->execute($criteria);

            $data = array_map(fn ($zone) => $zone->toArray(), $result->items);

            $pagination = [
                'total' => $result->total,
                'current_page' => $result->currentPage,
                'per_page' => $result->perPage,
                'last_page' => $result->lastPage,
            ];

            return ApiResponse::Pagination(
                data: $data,
                message: 'Zonas de envío listadas exitosamente',
                code: 200,
                pagination: $pagination
            );
        } catch (Exception $e) {
            return ApiResponse::error(
                message: $e->getMessage(),
                code: (int) ($e->getCode() ?: 400)
            );
        }
    }
}
