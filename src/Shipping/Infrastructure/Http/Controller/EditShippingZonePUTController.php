<?php

declare(strict_types=1);

namespace Src\Shipping\Infrastructure\Http\Controller;

use Exception;
use Illuminate\Http\JsonResponse;
use Src\Shared\Helper\ApiResponse;
use Src\Shipping\Application\UseCase\EditShippingZoneUseCase;
use Src\Shipping\Infrastructure\Http\Request\EditShippingZoneFormRequest;

final class EditShippingZonePUTController
{
    public function __construct(
        private readonly EditShippingZoneUseCase $useCase
    ) {}

    public function __invoke(string $id, EditShippingZoneFormRequest $request): JsonResponse
    {
        try {
            $zone = $this->useCase->execute(
                id: $id,
                name: (string) $request->input('name'),
                countries: $request->input('countries'),
                states: $request->input('states'),
                postalCodes: $request->input('postal_codes'),
                priority: (int) $request->input('priority', 0),
                isActive: (bool) $request->input('is_active', true)
            );

            return ApiResponse::success(
                data: $zone->toArray(),
                message: 'Zona de envío actualizada exitosamente',
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
