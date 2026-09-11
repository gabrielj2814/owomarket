<?php

declare(strict_types=1);

namespace Src\CentralCustomer\Infrastructure\Http\Controller;

use Illuminate\Http\JsonResponse;
use Src\CentralCustomer\Infrastructure\Http\Support\ResolvesAuthenticatedCustomer;
use Src\Monetization\Application\UseCases\GetOrderDeliveryStatusUseCase;
use Src\Monetization\Infrastructure\Eloquent\Models\OrderDeliveryConfirmation;

/**
 * El comprador consulta el expediente de entrega de su pedido (subsistema 3, fase B): que
 * evidencia subio la tienda al enviar, si ya declaro la entrega y si le toca confirmar.
 *
 * La comprobacion de propiedad se hace AQUI y no en el caso de uso, que solo proyecta. Es
 * deliberado: el mismo expediente lo lee tambien el comerciante desde su API, donde el filtro
 * es otro --su propia base-- y meter las dos reglas en el caso de uso acabaria con un
 * parametro «modo» que nadie sabria leer.
 */
final class GetCustomerDeliveryStatusGETController
{
    use ResolvesAuthenticatedCustomer;

    public function __construct(
        private readonly GetOrderDeliveryStatusUseCase $getStatus
    ) {}

    public function __invoke(string $orderId): JsonResponse
    {
        $customerId = $this->currentCustomerId();

        $esSuyo = OrderDeliveryConfirmation::where('order_id', $orderId)
            ->where('customer_id', $customerId)
            ->exists();

        if (! $esSuyo) {
            // 404 y no 403: confirmar que el pedido existe pero es de otro ya es filtrar
            // informacion a quien no deberia tenerla.
            return response()->json([
                'code' => 404,
                'status' => 'error',
                'message' => 'Pedido no encontrado.',
            ], 404);
        }

        return response()->json([
            'code' => 200,
            'status' => 'success',
            'data' => $this->getStatus->execute($orderId),
        ]);
    }
}
