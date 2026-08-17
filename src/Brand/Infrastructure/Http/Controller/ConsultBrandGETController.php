<?php

declare(strict_types=1);

namespace Src\Brand\Infrastructure\Http\Controller;

use Exception;
use Illuminate\Http\JsonResponse;
use Src\Brand\Application\UseCase\ConsultBrandByIdUseCase;
use Src\Shared\Helper\ApiResponse;

final class ConsultBrandGETController
{
    public function __construct(
        private readonly ConsultBrandByIdUseCase $useCase
    ) {}

    public function __invoke(int $id): JsonResponse
    {
        try {
            $brand = $this->useCase->execute($id);

            return ApiResponse::success(
                data: $brand->toArray(),
                message: 'Marca consultada exitosamente',
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
