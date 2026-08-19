<?php

declare(strict_types=1);

namespace Src\Product\Infrastructure\Http\Controller;

use Exception;
use Illuminate\Http\JsonResponse;
use Src\Product\Application\UseCase\DeleteProductUseCase;
use Src\Shared\Helper\ApiResponse;

final class DeleteProductDELETEController
{
    public function __construct(
        private readonly DeleteProductUseCase $useCase
    ) {}

    public function __invoke(string $id): JsonResponse
    {
        try {
            $this->useCase->execute($id);

            return ApiResponse::success(
                data: null,
                message: 'Producto eliminado exitosamente',
                code: 200
            );
        } catch (Exception $e) {
            return ApiResponse::error(
                message: $e->getMessage(),
                code: (int) ($e->getCode() ?: 404)
            );
        }
    }
}
