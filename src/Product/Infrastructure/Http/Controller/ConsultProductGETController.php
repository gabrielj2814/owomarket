<?php

declare(strict_types=1);

namespace Src\Product\Infrastructure\Http\Controller;

use Exception;
use Illuminate\Http\JsonResponse;
use Src\Product\Application\UseCase\ConsultProductByIdUseCase;
use Src\Product\Application\UseCase\ConsultProductBySlugUseCase;
use Src\Shared\Helper\ApiResponse;

final class ConsultProductGETController
{
    public function __construct(
        private readonly ConsultProductByIdUseCase $consultByIdUseCase,
        private readonly ConsultProductBySlugUseCase $consultBySlugUseCase
    ) {}

    public function __invoke(string $identifier): JsonResponse
    {
        try {
            // Check if identifier is a valid UUID, otherwise search by slug
            if (preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $identifier)) {
                $product = $this->consultByIdUseCase->execute($identifier);
            } else {
                $product = $this->consultBySlugUseCase->execute($identifier);
            }

            return ApiResponse::success(
                data: $product->toArray(),
                message: 'Producto consultado exitosamente',
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
