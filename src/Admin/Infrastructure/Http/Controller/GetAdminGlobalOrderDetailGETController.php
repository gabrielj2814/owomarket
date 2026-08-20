<?php

declare(strict_types=1);

namespace Src\Admin\Infrastructure\Http\Controller;

use Exception;
use Illuminate\Http\JsonResponse;
use Src\Admin\Application\UseCase\GetCentralOrderDetailForAdminUseCase;
use Src\Shared\Helper\ApiResponse;

final class GetAdminGlobalOrderDetailGETController
{
    public function __construct(
        private readonly GetCentralOrderDetailForAdminUseCase $useCase
    ) {}

    public function __invoke(string $id): JsonResponse
    {
        try {
            $data = $this->useCase->execute($id);

            return ApiResponse::success(
                data: $data,
                message: 'Detalle de orden obtenido exitosamente.'
            );
        } catch (Exception $e) {
            return ApiResponse::error(
                message: $e->getMessage(),
                code: (int) ($e->getCode() ?: 400)
            );
        }
    }
}
