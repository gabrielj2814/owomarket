<?php

declare(strict_types=1);

namespace Src\Shipping\Infrastructure\Http\Controller;

use Exception;
use Illuminate\Http\JsonResponse;
use Src\Shared\Helper\ApiResponse;
use Src\Shipping\Application\UseCase\CalculateShippingOptionsUseCase;
use Src\Shipping\Infrastructure\Http\Request\CalculateShippingFormRequest;

final class CalculateShippingPOSTController
{
    public function __construct(
        private readonly CalculateShippingOptionsUseCase $useCase
    ) {}

    public function __invoke(CalculateShippingFormRequest $request): JsonResponse
    {
        try {
            $result = $this->useCase->execute(
                orderValue: (float) $request->input('order_value'),
                totalWeight: (float) $request->input('total_weight', 0.0),
                country: $request->filled('country') ? (string) $request->input('country') : null,
                state: $request->filled('state') ? (string) $request->input('state') : null,
                postalCode: $request->filled('postal_code') ? (string) $request->input('postal_code') : null
            );

            return ApiResponse::success(
                data: $result->toArray(),
                message: 'Opciones de envío calculadas exitosamente',
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
