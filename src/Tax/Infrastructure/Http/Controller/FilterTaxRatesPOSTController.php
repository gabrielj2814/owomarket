<?php

declare(strict_types=1);

namespace Src\Tax\Infrastructure\Http\Controller;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Src\Shared\Helper\ApiResponse;
use Src\Tax\Application\DTOs\TaxRateFilterCriteria;
use Src\Tax\Application\UseCase\FilterTaxRatesUseCase;

final class FilterTaxRatesPOSTController
{
    public function __construct(
        private readonly FilterTaxRatesUseCase $useCase
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        try {
            $criteria = new TaxRateFilterCriteria(
                search: $request->filled('search') ? (string) $request->input('search') : null,
                country: $request->filled('country') ? (string) $request->input('country') : null,
                state: $request->filled('state') ? (string) $request->input('state') : null,
                isActive: $request->has('is_active') && $request->input('is_active') !== '' && $request->input('is_active') !== null
                    ? filter_var($request->input('is_active'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE)
                    : null,
                page: (int) $request->input('page', 1),
                perPage: (int) $request->input('prePage', $request->input('perPage', 10)),
                sortBy: (string) $request->input('sortBy', 'priority'),
                sortDirection: (string) $request->input('sortDirection', 'asc')
            );

            $result = $this->useCase->execute($criteria);

            $data = array_map(fn ($tax) => $tax->toArray(), $result->items);

            return ApiResponse::paginated(
                data: $data,
                total: $result->total,
                currentPage: $result->currentPage,
                perPage: $result->perPage,
                lastPage: $result->lastPage,
                message: 'Tasas de impuestos listadas exitosamente',
                code: 200
            );
        } catch (Exception $e) {
            return ApiResponse::error(
                message: $e->getMessage(),
                code: (int) ($e->getCode() ?: 400)
            );
        }
    }
}
