<?php

declare(strict_types=1);

namespace Src\Tenant\Infrastructure\Http\Controller;

use Exception;
use Illuminate\Http\JsonResponse;
use Src\Shared\Helper\ApiResponse;
use Src\Tenant\Application\UseCase\GetTenant360DetailUseCase;

final class GetAdminTenant360DataGETController
{
    public function __construct(
        private readonly GetTenant360DetailUseCase $useCase
    ) {}

    public function __invoke(string $id): JsonResponse
    {
        try {
            $data = $this->useCase->execute($id);

            return ApiResponse::success(
                data: $data,
                message: 'Expediente 360 obtenido exitosamente.'
            );
        } catch (Exception $e) {
            return ApiResponse::error(
                message: $e->getMessage(),
                code: (int) ($e->getCode() ?: 400)
            );
        }
    }
}
