<?php

declare(strict_types=1);

namespace Src\Shipping\Infrastructure\Http\Controller;

use Exception;
use Illuminate\Http\JsonResponse;
use Src\Shared\Helper\ApiResponse;
use Src\Shipping\Application\UseCase\ConsultShippingZoneByIdUseCase;

final class ConsultShippingZoneGETController
{
    public function __construct(
        private readonly ConsultShippingZoneByIdUseCase $useCase
    ) {}

    public function __invoke(string $id): JsonResponse
    {
        try {
            $zone = $this->useCase->execute($id);

            return ApiResponse::success(
                data: $zone->toArray(),
                message: 'Zona de envío consultada exitosamente',
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
