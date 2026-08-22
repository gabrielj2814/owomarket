<?php

declare(strict_types=1);

namespace Src\Order\Infrastructure\Http\Controller;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Src\Order\Application\UseCases\FilterOrdersUseCase;
use Src\Order\Infrastructure\Http\Request\FilterOrdersFormRequest;
use Src\Shared\Helper\ApiResponse;

final class FilterOrdersPOSTController extends Controller
{
    public function __construct(
        private readonly FilterOrdersUseCase $filterOrdersUseCase
    ) {}

    public function __invoke(FilterOrdersFormRequest $request): JsonResponse
    {
        $criteria = $request->toDto();
        $result = $this->filterOrdersUseCase->execute($criteria);

        return ApiResponse::paginated(
            data: $result->itemsToArray(),
            total: $result->total,
            currentPage: $result->currentPage,
            perPage: $result->perPage,
            lastPage: $result->lastPage,
            message: 'Órdenes filtradas exitosamente.'
        );
    }
}
