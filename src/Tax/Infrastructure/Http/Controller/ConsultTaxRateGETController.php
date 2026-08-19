<?php

declare(strict_types=1);

namespace Src\Tax\Infrastructure\Http\Controller;

use Exception;
use Illuminate\Http\JsonResponse;
use Src\Shared\Helper\ApiResponse;
use Src\Tax\Application\UseCase\ConsultTaxRateByIdUseCase;

final class ConsultTaxRateGETController
{
    public function __construct(
        private readonly ConsultTaxRateByIdUseCase $useCase
    ) {}

    public function __invoke(string $id): JsonResponse
    {
        try {
            $taxRate = $this->useCase->execute($id);

            return ApiResponse::success(
                data: $taxRate->toArray(),
                message: 'Tasa de impuesto consultada exitosamente',
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
