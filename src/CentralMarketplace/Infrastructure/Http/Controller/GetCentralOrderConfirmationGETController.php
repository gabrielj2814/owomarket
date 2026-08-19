<?php

declare(strict_types=1);

namespace Src\CentralMarketplace\Infrastructure\Http\Controller;

use Exception;
use Illuminate\Http\JsonResponse;
use Src\CentralMarketplace\Application\UseCases\GetCentralOrderConfirmationUseCase;
use Src\Shared\Helper\ApiResponse;

final class GetCentralOrderConfirmationGETController
{
    public function __construct(
        private readonly GetCentralOrderConfirmationUseCase $useCase
    ) {}

    public function __invoke(string $id): JsonResponse
    {
        try {
            $summary = $this->useCase->execute($id);

            return ApiResponse::success(
                data: $summary,
                message: 'Confirmación de pedido central obtenida exitosamente'
            );
        } catch (Exception $e) {
            return ApiResponse::error(
                message: $e->getMessage(),
                code: (int) ($e->getCode() ?: 404)
            );
        }
    }
}
