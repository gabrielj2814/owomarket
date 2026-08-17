<?php

declare(strict_types=1);

namespace Src\Product\Infrastructure\Http\Controller;

use Exception;
use Illuminate\Http\JsonResponse;
use Src\Product\Application\UseCase\UpdateProductStockUseCase;
use Src\Product\Infrastructure\Http\Request\UpdateProductStockFormRequest;
use Src\Shared\Helper\ApiResponse;

final class UpdateProductStockPATCHController
{
    public function __construct(
        private readonly UpdateProductStockUseCase $useCase
    ) {}

    public function __invoke(string $id, UpdateProductStockFormRequest $request): JsonResponse
    {
        try {
            $quantity = (int) $request->input('quantity');

            $this->useCase->execute($id, $quantity);

            return ApiResponse::success(
                data: null,
                message: 'Stock del producto actualizado exitosamente',
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
