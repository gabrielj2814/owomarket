<?php

declare(strict_types=1);

namespace Src\CentralCustomer\Application\DTOs;

/**
 * El pedido y el articulo concretos que se estan reclamando, ya resueltos y ya comprobados
 * como del comprador que reclama.
 *
 * ## Por que un DTO y no el modelo
 *
 * Un pedido central es un `CentralOrder` con sus `CentralOrderItem`; uno de escaparate son dos
 * filas de la base de un inquilino. No tienen ninguna clase en comun y no la van a tener: el
 * segundo ni siquiera vive en la misma base de datos.
 *
 * Esto es lo poco que las reglas de una reclamacion necesitan de verdad. Que el adaptador
 * central tenga a mano el modelo entero no es razon para pasearlo: cuanto mas lleve este
 * objeto, mas facil es que una regla empiece a depender de algo que el otro camino no tiene.
 *
 * `tenantOrderId` es el que conecta con el dinero --`platform_commissions.order_id` guarda
 * ese-- y por eso no es opcional aqui aunque la columna lo sea.
 */
final class ClaimableOrderData
{
    public function __construct(
        /** El identificador que se guarda en `order_id`: central o de tienda segun el origen. */
        public readonly string $orderId,
        /** 'central' | 'storefront'. Es lo unico que distingue a que base apunta `orderId`. */
        public readonly string $orderSource,
        public readonly string $orderNumber,
        public readonly string $customerEmail,
        public readonly string $tenantId,
        public readonly string $tenantOrderId,
        public readonly string $productId,
        public readonly string $productName,
        public readonly float $amount,
    ) {}
}
