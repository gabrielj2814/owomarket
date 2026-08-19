<?php

declare(strict_types=1);

namespace Src\CentralCustomer\Infrastructure\Http\Controller;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Src\CentralCustomer\Application\UseCases\GetCentralCustomerProfileUseCase;
use Src\Shared\Helper\ApiResponse;

final class GetCustomerProfileGETController
{
    public function __construct(
        private readonly GetCentralCustomerProfileUseCase $useCase
    ) {}

    public function __invoke(string $id): JsonResponse
    {
        try {
            $customer = $this->useCase->execute($id);

            return ApiResponse::success(
                data: $customer,
                message: 'Perfil de cliente recuperado exitosamente',
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
