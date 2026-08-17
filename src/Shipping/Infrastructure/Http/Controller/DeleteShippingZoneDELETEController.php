<?php

declare(strict_types=1);

namespace Src\Shipping\Infrastructure\Http\Controller;

use Exception;
use Illuminate\Http\JsonResponse;
use Src\Shared\Helper\ApiResponse;
use Src\Shipping\Application\UseCase\DeleteShippingZoneUseCase;

final class DeleteShippingZoneDELETEController
{
    public function __construct(
        private readonly DeleteShippingZoneUseCase $useCase
    ) {}

    public function __invoke(string $id): JsonResponse
    {
        try {
            $this->useCase->execute($id);

            return ApiResponse::success(
                data: null,
                message: 'Zona de envío eliminada exitosamente',
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
