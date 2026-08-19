<?php

declare(strict_types=1);

namespace Src\Attribute\Infrastructure\Http\Controller;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Src\Attribute\Application\DTOs\AttributeFilterCriteria;
use Src\Attribute\Application\UseCase\FilterAttributesUseCase;
use Src\Shared\Helper\ApiResponse;

final class FilterAttributesPOSTController
{
    public function __construct(
        private readonly FilterAttributesUseCase $useCase
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        try {
            $criteria = new AttributeFilterCriteria(
                search: $request->filled('search') ? (string) $request->input('search') : null,
                type: $request->filled('type') ? (string) $request->input('type') : null,
                isFilterable: $request->has('is_filterable') && $request->input('is_filterable') !== ''
                    ? filter_var($request->input('is_filterable'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE)
                    : null,
                isVisible: $request->has('is_visible') && $request->input('is_visible') !== ''
                    ? filter_var($request->input('is_visible'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE)
                    : null,
                page: (int) $request->input('page', 1),
                perPage: (int) $request->input('prePage', $request->input('perPage', 10)),
                sortBy: (string) $request->input('sortBy', 'position'),
                sortDirection: (string) $request->input('sortDirection', 'asc')
            );

            $result = $this->useCase->execute($criteria);

            $data = array_map(fn ($attr) => $attr->toArray(), $result->items);

            $pagination = [
                'total' => $result->total,
                'current_page' => $result->currentPage,
                'per_page' => $result->perPage,
                'last_page' => $result->lastPage,
            ];

            return ApiResponse::Pagination(
                data: $data,
                message: 'Atributos listados exitosamente',
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
