<?php

declare(strict_types=1);

namespace Src\Product\Infrastructure\Http\Controller;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Src\Product\Application\DTOs\ProductFilterCriteria;
use Src\Product\Application\UseCase\FilterProductsUseCase;
use Src\Shared\Helper\ApiResponse;

final class FilterProductsPOSTController
{
    public function __construct(
        private readonly FilterProductsUseCase $useCase
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $criteria = new ProductFilterCriteria(
            search: $request->filled('search') ? (string) $request->input('search') : null,
            categoryId: $request->filled('category_id') ? (int) $request->input('category_id') : null,
            brandId: $request->filled('brand_id') ? (int) $request->input('brand_id') : null,
            minPrice: $request->filled('min_price') ? (float) $request->input('min_price') : null,
            maxPrice: $request->filled('max_price') ? (float) $request->input('max_price') : null,
            isVisible: $request->has('is_visible') && $request->input('is_visible') !== null && $request->input('is_visible') !== '' ? (bool) $request->input('is_visible') : null,
            isFeatured: $request->has('is_featured') && $request->input('is_featured') !== null && $request->input('is_featured') !== '' ? (bool) $request->input('is_featured') : null,
            isDigital: $request->has('is_digital') && $request->input('is_digital') !== null && $request->input('is_digital') !== '' ? (bool) $request->input('is_digital') : null,
            inStock: $request->has('in_stock') && $request->input('in_stock') !== null && $request->input('in_stock') !== '' ? (bool) $request->input('in_stock') : null,
            page: (int) $request->input('page', 1),
            perPage: (int) $request->input('per_page', 10),
            sortBy: (string) $request->input('sort_by', 'created_at'),
            sortDirection: (string) $request->input('sort_direction', 'desc')
        );

        $result = $this->useCase->execute($criteria);

        $data = array_map(fn ($product) => $product->toArray(), $result->items);

        return ApiResponse::paginated(
            data: $data,
            total: $result->total,
            currentPage: $result->currentPage,
            perPage: $result->perPage,
            lastPage: $result->lastPage,
            message: 'Productos listados exitosamente',
            code: 200
        );
    }
}
