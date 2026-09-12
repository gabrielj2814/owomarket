<?php

declare(strict_types=1);

namespace Src\Marketplace\Infrastructure\Http\Controller;

use Illuminate\Http\JsonResponse;
use Src\Marketplace\Infrastructure\Http\Support\ResolvesStorefrontCustomer;
use Src\Monetization\Application\UseCases\GetOrderDeliveryStatusUseCase;
use Src\Monetization\Infrastructure\Eloquent\Models\OrderDeliveryConfirmation;
use Src\Shared\Helper\ApiResponse;

/**
 * El expediente de entrega de un pedido de escaparate, visto por su comprador.
 *
 * Gemelo de `GetCustomerDeliveryStatusGETController`, que hace lo mismo en el dominio central.
 * Cambia UNA cosa: de donde sale la identidad --la sesion del escaparate en vez del guard
 * `central_customer`--. El caso de uso que proyecta el expediente es el mismo.
 *
 * La comprobacion de propiedad se hace aqui y no en el caso de uso, igual que en el gemelo
 * central: el caso de uso solo proyecta, y meter dos reglas de propiedad dentro acabaria con un
 * parametro «modo» que nadie sabria leer.
 */
final class GetStorefrontDeliveryStatusGETController
{
    use ResolvesStorefrontCustomer;

    public function __construct(
        private readonly GetOrderDeliveryStatusUseCase $getStatus
    ) {}

    public function __invoke(string $orderId): JsonResponse
    {
        $customerId = $this->currentStorefrontCustomerId();

        if ($customerId === '') {
            return ApiResponse::error('Inicia sesión para ver este pedido.', 401);
        }

        $esSuyo = OrderDeliveryConfirmation::where('order_id', $orderId)
            ->where('customer_id', $customerId)
            ->exists();

        if (! $esSuyo) {
            // 404 y no 403: confirmar que el pedido existe pero es de otro ya es filtrar
            // informacion a quien no deberia tenerla.
            return ApiResponse::error('Pedido no encontrado.', 404);
        }

        return ApiResponse::success($this->getStatus->execute($orderId), 'Expediente de entrega.');
    }
}
