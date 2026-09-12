<?php

declare(strict_types=1);

namespace Src\Marketplace\Infrastructure\Eloquent\Repositories;

use Illuminate\Support\Facades\DB;
use Src\CentralCustomer\Application\Contracts\ClaimableOrderLocator;
use Src\CentralCustomer\Application\DTOs\ClaimableOrderData;

/**
 * El pedido reclamable cuando la compra se hizo en el escaparate de una tienda.
 *
 * Vive en `Marketplace` y no junto al central porque es **quien conoce las tablas del
 * inquilino**. Corre dentro del contexto de tenencia que monta el middleware de las rutas de
 * tienda, asi que `orders`, `order_items` y `customers` son las de esa tienda.
 *
 * ## Quien es el dueño de un pedido de escaparate
 *
 * El mismo criterio exacto que `ListStorefrontCustomerOrdersUseCase`: el `customer_id` del
 * pedido tiene que estar entre los `customers` de la tienda cuyo `central_uuid` es el del
 * comprador que reclama. **Nunca el correo.** El correo se escribe a mano en el checkout, asi
 * que aceptarlo como prueba de identidad dejaria abrir una reclamacion --que mueve dinero--
 * sobre la compra de otro.
 *
 * Quien compro como invitado no tiene ese enlace y aqui no encuentra nada. Es el precio
 * aceptado del camino elegido en la fase 1, y el checkout lo advierte antes de pagar.
 *
 * ## Por que consultas crudas
 *
 * Igual que en la fase 1: el modelo `Order` arrastra relaciones y ambitos pensados para el
 * backoffice del comerciante. Aqui solo hacen falta seis campos.
 */
final class StorefrontClaimableOrderLocator implements ClaimableOrderLocator
{
    public function find(string $orderId, string $productId, string $customerId): ?ClaimableOrderData
    {
        if (trim($customerId) === '') {
            return null;
        }

        $pedido = DB::table('orders')->where('id', $orderId)->first();

        if ($pedido === null) {
            return null;
        }

        $esSuyo = DB::table('customers')
            ->where('id', $pedido->customer_id)
            ->where('central_uuid', $customerId)
            ->exists();

        if (! $esSuyo) {
            return null;
        }

        $articulo = DB::table('order_items')
            ->where('order_id', $pedido->id)
            ->where('product_id', $productId)
            ->first();

        if ($articulo === null) {
            return null;
        }

        $correo = (string) (DB::table('customers')->where('id', $pedido->customer_id)->value('email') ?? '');

        return new ClaimableOrderData(
            orderId: (string) $pedido->id,
            orderSource: 'storefront',
            orderNumber: (string) $pedido->order_number,
            customerEmail: $correo,
            tenantId: (string) (tenant('id') ?? ''),
            // En una venta de escaparate el pedido de tienda ES el pedido. No hay pedido
            // central del que colgar, y por eso el subsistema 5 indexa el dinero por aqui.
            tenantOrderId: (string) $pedido->id,
            productId: (string) $articulo->product_id,
            productName: (string) $articulo->product_name,
            amount: (float) $articulo->total,
        );
    }
}
