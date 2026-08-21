<?php

declare(strict_types=1);

namespace Src\CentralCustomer\Infrastructure\Http\Controller;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Src\CentralCustomer\Application\UseCases\GetCentralCustomerProfileUseCase;
use Src\CentralCustomer\Infrastructure\Http\Support\ResolvesAuthenticatedCustomer;
use Src\Shared\Helper\ApiResponse;

final class GetCustomerProfileGETController
{
    use ResolvesAuthenticatedCustomer;

    public function __construct(
        private readonly GetCentralCustomerProfileUseCase $useCase
    ) {}

    public function __invoke(string $id): JsonResponse
    {
        // Antes cualquier sesión (o ninguna) podía leer el perfil de otro
        // comprador con solo cambiar el {id} de la URL (hallazgo A3).
        if ($denied = $this->denyIfNotOwnProfile($id)) {
            return $denied;
        }

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
