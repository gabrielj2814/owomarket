<?php

declare(strict_types=1);

namespace Src\CentralCustomer\Application\Contracts;

use Src\CentralCustomer\Application\DTOs\ClaimableOrderData;

/**
 * De donde sale el pedido que se reclama.
 *
 * Existe porque el mismo subsistema tiene que atender dos origenes que no comparten ni modelo
 * ni base de datos: el pedido unificado del marketplace central y el pedido de una tienda.
 * Sin esta frontera, `CreateCustomerReturnRequestUseCase` tendria que importar modelos
 * centrales **y** del inquilino y saber en que contexto de tenencia corre.
 *
 * ## La comprobacion de propiedad vive en el adaptador, no fuera
 *
 * `find()` recibe el id del comprador y devuelve nada si el pedido no es suyo. No hay una
 * version que devuelva el pedido «sin comprobar» para que el llamante decida: esa firma
 * permitiria olvidarse de comprobar, y el precio de olvidarlo aqui es que alguien reclame el
 * pedido de otro --con su direccion y su importe--.
 *
 * Cada implementacion sabe que significa «es tuyo» en su mundo: en el central lo dice
 * `central_orders.customer_id`; en una tienda, `customers.central_uuid`. **Nunca el correo**,
 * que se escribe a mano en el checkout.
 */
interface ClaimableOrderLocator
{
    /**
     * @param  string  $customerId  Id del comprador CENTRAL. Es el unico que los subsistemas 3
     *                              y 5 conocen, tambien para las ventas de escaparate.
     * @return ClaimableOrderData|null Nada si el pedido no existe, no es de ese comprador, o
     *                                 el producto no esta en el.
     */
    public function find(string $orderId, string $productId, string $customerId): ?ClaimableOrderData;
}
