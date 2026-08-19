<?php

declare(strict_types=1);

namespace Src\Tax\Infrastructure\Http\Controller;

use Exception;
use Illuminate\Http\JsonResponse;
use Src\Shared\Helper\ApiResponse;
use Src\Tax\Application\UseCase\CalculateTaxUseCase;
use Src\Tax\Infrastructure\Http\Request\CalculateTaxFormRequest;

final class CalculateTaxPOSTController
{
    public function __construct(
        private readonly CalculateTaxUseCase $useCase
    ) {}

    public function __invoke(CalculateTaxFormRequest $request): JsonResponse
    {
        try {
            $result = $this->useCase->execute(
                subtotal: (float) $request->input('subtotal'),
                country: $request->filled('country') ? (string) $request->input('country') : null,
                state: $request->filled('state') ? (string) $request->input('state') : null,
                city: $request->filled('city') ? (string) $request->input('city') : null,
                zip: $request->filled('zip') ? (string) $request->input('zip') : null
            );

            return ApiResponse::success(
                data: $result->toArray(),
                message: 'Impuesto calculado exitosamente',
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
