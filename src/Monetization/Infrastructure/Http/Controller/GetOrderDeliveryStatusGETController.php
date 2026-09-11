<?php

declare(strict_types=1);

namespace Src\Monetization\Infrastructure\Http\Controller;

use Illuminate\Http\JsonResponse;
use Src\Monetization\Application\UseCases\GetOrderDeliveryStatusUseCase;

/**
 * El expediente de entrega de un pedido, para la pantalla del comerciante (subsistema 3).
 *
 * El comprador tiene el suyo en `CentralCustomer`, con su propia comprobacion de propiedad.
 * Aqui la tienda solo ve pedidos de su propia base, que es lo que ya acota `tenantApi`.
 */
final class GetOrderDeliveryStatusGETController
{
    public function __construct(
        private readonly GetOrderDeliveryStatusUseCase $getStatus
    ) {}

    public function __invoke(string $orderId): JsonResponse
    {
        return response()->json([
            'code' => 200,
            'status' => 'success',
            'data' => $this->getStatus->execute($orderId),
        ]);
    }
}
