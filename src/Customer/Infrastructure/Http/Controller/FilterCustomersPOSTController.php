<?php

declare(strict_types=1);

namespace Src\Customer\Infrastructure\Http\Controller;

use Exception;
use Illuminate\Http\JsonResponse;
use Src\Customer\Application\UseCases\FilterCustomersUseCase;
use Src\Customer\Infrastructure\Http\Request\FilterCustomersFormRequest;
use Src\Shared\Helper\ApiResponse;

final class FilterCustomersPOSTController
{
    public function __construct(
        private readonly FilterCustomersUseCase $useCase
    ) {}

    public function __invoke(FilterCustomersFormRequest $request): JsonResponse
    {
        try {
            $result = $this->useCase->execute($request->toDto());

            return ApiResponse::paginated(
                data: $result->itemsToArray(),
                total: $result->total,
                currentPage: $result->currentPage,
                perPage: $result->perPage,
                lastPage: $result->lastPage,
                message: 'Clientes listados exitosamente'
            );
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Ocurrió un error inesperado al filtrar clientes: '.$e->getMessage(),
            ], 500);
        }
    }
}
