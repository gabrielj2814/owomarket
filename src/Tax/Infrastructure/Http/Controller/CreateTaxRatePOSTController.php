<?php

declare(strict_types=1);

namespace Src\Tax\Infrastructure\Http\Controller;

use Exception;
use Illuminate\Http\JsonResponse;
use Src\Shared\Helper\ApiResponse;
use Src\Tax\Application\UseCase\CreateTaxRateUseCase;
use Src\Tax\Infrastructure\Http\Request\CreateTaxRateFormRequest;

final class CreateTaxRatePOSTController
{
    public function __construct(
        private readonly CreateTaxRateUseCase $useCase
    ) {}

    public function __invoke(CreateTaxRateFormRequest $request): JsonResponse
    {
        try {
            $taxRate = $this->useCase->execute(
                name: (string) $request->input('name'),
                rate: (float) $request->input('rate'),
                country: $request->filled('country') ? (string) $request->input('country') : null,
                state: $request->filled('state') ? (string) $request->input('state') : null,
                city: $request->filled('city') ? (string) $request->input('city') : null,
                zip: $request->filled('zip') ? (string) $request->input('zip') : null,
                priority: (int) $request->input('priority', 0),
                isActive: (bool) $request->input('is_active', true)
            );

            return ApiResponse::success(
                data: $taxRate->toArray(),
                message: 'Tasa de impuesto creada exitosamente',
                code: 201
            );
        } catch (Exception $e) {
            return ApiResponse::error(
                message: $e->getMessage(),
                code: (int) ($e->getCode() ?: 400)
            );
        }
    }
}
