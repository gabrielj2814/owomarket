<?php

declare(strict_types=1);

namespace Src\CentralCustomer\Infrastructure\Eloquent\Repositories;

use Src\CentralCustomer\Application\Contracts\ClaimableOrderLocator;
use Src\CentralCustomer\Application\DTOs\ClaimableOrderData;
use Src\Order\Infrastructure\Eloquent\Models\CentralOrder;

/**
 * El pedido reclamable cuando la compra se hizo en el marketplace central.
 *
 * Es la busqueda que `CreateCustomerReturnRequestUseCase` hacia dentro de si mismo hasta la
 * fase 2 del escaparate. No cambia ni una condicion: un pedido central es del comprador si su
 * `customer_id` coincide, y punto.
 */
final class CentralClaimableOrderLocator implements ClaimableOrderLocator
{
    public function find(string $orderId, string $productId, string $customerId): ?ClaimableOrderData
    {
        $pedido = CentralOrder::with('items')
            ->where('id', $orderId)
            ->where('customer_id', $customerId)
            ->first();

        if ($pedido === null) {
            return null;
        }

        $articulo = $pedido->items->firstWhere('product_id', $productId);

        if ($articulo === null) {
            return null;
        }

        return new ClaimableOrderData(
            orderId: (string) $pedido->id,
            orderSource: 'central',
            orderNumber: (string) $pedido->order_number,
            customerEmail: (string) $pedido->customer_email,
            tenantId: (string) $articulo->tenant_id,
            // Un articulo central sin despachar todavia no tiene pedido de tienda. Se deja
            // vacio y es el caso de uso quien decide que hacer con eso -- aqui solo se
            // informa de lo que hay.
            tenantOrderId: (string) ($articulo->tenant_order_id ?? ''),
            productId: (string) $articulo->product_id,
            productName: (string) $articulo->product_name,
            amount: (float) $articulo->total,
        );
    }
}
