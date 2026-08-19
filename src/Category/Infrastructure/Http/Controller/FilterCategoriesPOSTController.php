<?php

declare(strict_types=1);

namespace Src\Category\Infrastructure\Http\Controller;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Src\Category\Application\DTOs\CategoryFilterCriteria;
use Src\Category\Application\UseCase\FilterCategoriesUseCase;
use Src\Shared\Helper\ApiResponse;

class FilterCategoriesPOSTController extends Controller
{
    public function __construct(
        protected FilterCategoriesUseCase $filterCategoriesUseCase
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        try {
            $criteria = new CategoryFilterCriteria(
                search: $request->filled('search') ? (string) $request->input('search') : null,
                isActive: $request->has('is_active') && $request->input('is_active') !== '' && $request->input('is_active') !== null
                    ? filter_var($request->input('is_active'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE)
                    : null,
                parentId: $request->filled('parent_id') ? (int) $request->input('parent_id') : null,
                fechaDesdeUTC: $request->filled('fechaDesdeUTC') ? (string) $request->input('fechaDesdeUTC') : null,
                fechaHastaUTC: $request->filled('fechaHastaUTC') ? (string) $request->input('fechaHastaUTC') : null,
                page: (int) $request->input('page', 1),
                perPage: (int) $request->input('prePage', $request->input('perPage', 50))
            );

            $result = $this->filterCategoriesUseCase->execute($criteria);

            $data = array_map(fn ($cat) => $cat->toArray(), $result->items);

            $pagination = [
                'total' => $result->total,
                'current_page' => $result->currentPage,
                'per_page' => $result->perPage,
                'last_page' => $result->lastPage,
            ];

            return ApiResponse::Pagination(
                data: $data,
                message: 'Categorías obtenidas exitosamente',
                code: 200,
                pagination: $pagination
            );
        } catch (Exception $e) {
            return ApiResponse::error(message: $e->getMessage(), code: 400);
        }
    }
}
