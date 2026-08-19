<?php

declare(strict_types=1);

namespace Src\Shipping\Infrastructure\Http\Controller;

use Exception;
use Illuminate\Http\JsonResponse;
use Src\Shared\Helper\ApiResponse;
use Src\Shipping\Application\UseCase\CreateShippingRateUseCase;
use Src\Shipping\Infrastructure\Http\Request\CreateShippingRateFormRequest;

final class CreateShippingRatePOSTController
{
    public function __construct(
        private readonly CreateShippingRateUseCase $useCase
    ) {}

    public function __invoke(string $shippingZoneId, CreateShippingRateFormRequest $request): JsonResponse
    {
        try {
            $rate = $this->useCase->execute(
                shippingZoneId: $shippingZoneId,
                name: (string) $request->input('name'),
                type: (string) $request->input('type'),
                cost: (float) $request->input('cost'),
                minValue: $request->filled('min_value') ? (float) $request->input('min_value') : null,
                maxValue: $request->filled('max_value') ? (float) $request->input('max_value') : null,
                isActive: (bool) $request->input('is_active', true)
            );

            return ApiResponse::success(
                data: $rate->toArray(),
                message: 'Tarifa de envío creada exitosamente',
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
