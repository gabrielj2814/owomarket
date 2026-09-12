<?php

declare(strict_types=1);

namespace Src\Marketplace\Infrastructure\Http\Controller;

use Illuminate\Http\JsonResponse;
use Src\Marketplace\Application\UseCase\ListStorefrontCustomerOrdersUseCase;
use Src\Marketplace\Infrastructure\Http\Support\ResolvesStorefrontCustomer;
use Src\Shared\Helper\ApiResponse;

/**
 * Los pedidos del comprador en esta tienda (fase 1 del plan del escaparate).
 *
 * Sin sesion devuelve 401 y no una lista vacia: son cosas distintas --«no has entrado» frente a
 * «no has comprado nada»-- y la pantalla tiene que poder decir cual de las dos es.
 */
final class ListStorefrontMyOrdersGETController
{
    use ResolvesStorefrontCustomer;

    public function __construct(
        private readonly ListStorefrontCustomerOrdersUseCase $useCase
    ) {}

    public function __invoke(): JsonResponse
    {
        $customerId = $this->currentStorefrontCustomerId();

        if ($customerId === '') {
            return ApiResponse::error('Inicia sesión para ver tus pedidos.', 401);
        }

        return ApiResponse::success(
            data: $this->useCase->execute($customerId),
            message: 'Pedidos recuperados correctamente.'
        );
    }
}
