<?php

declare(strict_types=1);

namespace Src\Order\Infrastructure\Http\Controller;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Src\Order\Application\UseCases\FilterOrdersUseCase;
use Src\Order\Infrastructure\Http\Request\FilterOrdersFormRequest;

final class FilterOrdersPOSTController extends Controller
{
    public function __construct(
        private readonly FilterOrdersUseCase $filterOrdersUseCase
    ) {}

    public function __invoke(FilterOrdersFormRequest $request): JsonResponse
    {
        $criteria = $request->toDto();
        $result = $this->filterOrdersUseCase->execute($criteria);

        return response()->json([
            'status' => 'success',
            'code' => 200,
            'message' => 'Órdenes filtradas exitosamente.',
            'data' => $result->toArray(),
        ], 200);
    }
}
