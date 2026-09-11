<?php

declare(strict_types=1);

namespace Src\Monetization\Application\UseCases;

use Exception;
use Illuminate\Support\Facades\DB;
use Src\Monetization\Infrastructure\Eloquent\Models\OrderDeliveryConfirmation;

/**
 * El comprador confirma que recibio el pedido, y **eso si libera el dinero**.
 *
 * Es la mitad util del subsistema 3: antes de esto el unico que podia hacer retirable el
 * importe de una venta era el propio comerciante, desde su propia API.
 *
 * Sin `final`: los tests lo doblan con Mockery (ver `reglas.md`).
 */
class ConfirmOrderDeliveryUseCase
{
    public function __construct(
        private readonly ReleaseOrderCommissionUseCase $release
    ) {}

    /**
     * @param  string  $customerId  El comprador AUTENTICADO. No se toma de la peticion.
     * @param  array<int, array<string, mixed>>|null  $evidence  Pruebas de recepcion (fase B).
     *
     * @throws Exception 404 si no hay expediente, 403 si no es su pedido, 409 si ya se libero.
     */
    public function execute(string $orderId, string $customerId, ?array $evidence = null): OrderDeliveryConfirmation
    {
        return DB::transaction(function () use ($orderId, $customerId, $evidence) {
            // El bloqueo serializa dos confirmaciones simultaneas. Sin el, las dos leerian
            // `released_at` a nulo, las dos pasarian y se llamaria dos veces a la liberacion
            // --que es idempotente, asi que el daño real seria un expediente con datos de la
            // segunda; pero la leccion C3 de este repositorio es no dejarlo al azar--.
            $expediente = OrderDeliveryConfirmation::where('order_id', $orderId)
                ->lockForUpdate()
                ->first();

            if ($expediente === null) {
                throw new Exception('Este pedido todavía no tiene una entrega registrada por la tienda.', 404);
            }

            // Frontera de confianza: solo el comprador de ESTE pedido lo confirma. Un
            // expediente sin comprador enlazado no se puede confirmar por nadie --se
            // liberara por plazo-- porque no hay forma de comprobar quien es quien.
            if ($expediente->customer_id === null || $expediente->customer_id !== $customerId) {
                throw new Exception('Este pedido no te pertenece.', 403);
            }

            if ($expediente->declared_delivered_at === null) {
                throw new Exception('La tienda todavía no ha declarado la entrega de este pedido.', 409);
            }

            if ($expediente->released_at !== null) {
                throw new Exception('Esta entrega ya estaba confirmada.', 409);
            }

            $expediente->confirmed_at = now();
            $expediente->released_by = 'customer';
            $expediente->released_at = now();

            if ($evidence !== null) {
                $expediente->confirmation_evidence = $evidence;
            }

            $expediente->save();

            // Fuera de la transaccion no se puede, porque entonces una confirmacion podria
            // quedar registrada sin liberar. Dentro es seguro: las dos escrituras viven en
            // la base central.
            $this->release->execute($orderId);

            return $expediente;
        });
    }
}
