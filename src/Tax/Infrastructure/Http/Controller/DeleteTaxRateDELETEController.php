<?php

declare(strict_types=1);

namespace Src\Tax\Infrastructure\Http\Controller;

use Exception;
use Illuminate\Http\JsonResponse;
use Src\Shared\Helper\ApiResponse;
use Src\Tax\Application\UseCase\DeleteTaxRateUseCase;

final class DeleteTaxRateDELETEController
{
    public function __construct(
        private readonly DeleteTaxRateUseCase $useCase
    ) {}

    public function __invoke(string $id): JsonResponse
    {
        try {
            $this->useCase->execute($id);

            return ApiResponse::success(
                data: null,
                message: 'Tasa de impuesto eliminada exitosamente',
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
