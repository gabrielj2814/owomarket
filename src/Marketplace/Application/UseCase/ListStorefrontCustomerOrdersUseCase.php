<?php

declare(strict_types=1);

namespace Src\Marketplace\Application\UseCase;

use Illuminate\Support\Facades\DB;
use Src\CentralCustomer\Application\Service\ClaimWindow;
use Src\CentralCustomer\Infrastructure\Eloquent\Models\CustomerReturnRequest;
use Src\Monetization\Infrastructure\Eloquent\Models\OrderDeliveryConfirmation;

/**
 * Los pedidos que un comprador ha hecho EN ESTA TIENDA (fase 1 de
 * `planes/por_hacer/PLAN_PEDIDOS_ESCAPARATE.md`).
 *
 * ## La identidad entra por parámetro, y es la central
 *
 * Se recibe el `centralCustomerId` —el que el SSO dejó en la sesión— y se busca por
 * `customers.central_uuid`. **Nunca por correo**: el correo se escribe a mano en el checkout,
 * así que aceptarlo como prueba de identidad dejaría que cualquiera viera los pedidos de otro
 * escribiendo su dirección.
 *
 * Un comprador que compró como invitado no tiene ese enlace y no ve nada. Es el precio
 * aceptado del camino elegido, y el checkout lo advierte antes de pagar.
 *
 * ## Por qué consultas crudas y no el modelo Order
 *
 * Esto corre dentro del contexto del inquilino, sobre su propia base. `Order` arrastra
 * relaciones y ámbitos pensados para el backoffice del comerciante; aquí solo hace falta la
 * proyección que el comprador ve de sus propias compras. Traer el modelo entero sería pagar
 * por lo que no se usa y arriesgarse a devolver campos internos.
 */
final class ListStorefrontCustomerOrdersUseCase
{
    /** Un comprador no necesita ver su historial entero de golpe en una pantalla así. */
    private const LIMITE = 50;

    public function __construct(
        private readonly ClaimWindow $ventanaDeReclamacion
    ) {}

    /**
     * @return array<int, array<string, mixed>>
     */
    public function execute(string $centralCustomerId): array
    {
        if (trim($centralCustomerId) === '') {
            return [];
        }

        $customerIds = DB::table('customers')
            ->where('central_uuid', $centralCustomerId)
            ->pluck('id')
            ->all();

        if ($customerIds === []) {
            return [];
        }

        $pedidos = DB::table('orders')
            ->whereIn('customer_id', $customerIds)
            ->orderByDesc('created_at')
            ->limit(self::LIMITE)
            ->get();

        if ($pedidos->isEmpty()) {
            return [];
        }

        $ids = $pedidos->pluck('id')->all();

        // Los expedientes de entrega, en UNA consulta. Son los que dicen si al comprador le
        // toca confirmar algo, que es la razón por la que esta pantalla existe.
        $entregas = OrderDeliveryConfirmation::whereIn('order_id', $ids)
            ->get()
            ->keyBy('order_id');

        // Los artículos, también en una. Sin esto serían N consultas para una lista de N.
        $items = DB::table('order_items')
            ->whereIn('order_id', $ids)
            ->get()
            ->groupBy('order_id');

        /*
         * Las reclamaciones que este comprador ya abrió sobre estos pedidos (fase 2).
         *
         * Viven en la tabla CENTRAL aunque el pedido sea de tienda, así que esta consulta
         * cruza de base a propósito: `CustomerReturnRequest` declara su propia conexión.
         *
         * Se filtra también por `customer_id` y no solo por `order_id`: las reclamaciones que
         * se pintan aquí son las de quien está mirando, y ceñir la consulta a eso es lo que
         * hace imposible que un fallo futuro en el filtro de pedidos enseñe la reclamación de
         * otra persona.
         */
        $reclamaciones = CustomerReturnRequest::where('customer_id', $centralCustomerId)
            ->where('order_source', 'storefront')
            ->whereIn('order_id', $ids)
            // La más reciente de cada artículo es la que se pinta: `keyBy` se queda con la
            // última, así que el orden ascendente es lo que hace que gane la nueva y no la
            // vieja que ya se rechazó.
            ->orderBy('created_at')
            ->get()
            ->keyBy(fn ($r) => $r->order_id.'|'.$r->product_id);

        return $pedidos->map(function ($pedido) use ($entregas, $items, $reclamaciones) {
            $entrega = $entregas->get($pedido->id);

            /*
             * Si se puede reclamar lo decide el SERVIDOR, con las mismas reglas que
             * `CreateCustomerReturnRequestUseCase` aplicará al recibir la solicitud: entrega
             * declarada y dentro de la ventana. Una pantalla que llegue a su propia conclusión
             * acaba ofreciendo un botón que el backend rechaza.
             *
             * La cédula NO entra aquí: es una condición del comprador, no del pedido, y la
             * pantalla la avisa antes de abrir el formulario para no hacerle escribir la
             * explicación entera y perderla.
             */
            $entregadoEl = $entrega?->released_at
                ?? $entrega?->confirmed_at
                ?? $entrega?->declared_delivered_at;

            $enPlazo = $entregadoEl !== null && $this->ventanaDeReclamacion->isOpenFor($entregadoEl);

            return [
                'id' => $pedido->id,
                'order_number' => $pedido->order_number,
                'status' => $pedido->status,
                'total' => (float) $pedido->total,
                'currency' => $pedido->currency ?? 'USD',
                'created_at' => $pedido->created_at,
                'items' => collect($items->get($pedido->id, []))
                    ->map(function ($i) use ($pedido, $reclamaciones, $enPlazo) {
                        $reclamacion = $reclamaciones->get($pedido->id.'|'.$i->product_id);

                        return [
                            'id' => $i->id,
                            'product_id' => $i->product_id,
                            'product_name' => $i->product_name,
                            'quantity' => (int) $i->quantity,
                            'price' => (float) $i->price,
                            /*
                             * Lo ya reclamado se enseña EN LUGAR del botón. Dejar el botón
                             * puesto haría que el comprador lo pulsara para recibir un «ya
                             * existe una solicitud activa» que no tenía forma de prever.
                             */
                            'claim' => $reclamacion === null ? null : [
                                'id' => $reclamacion->id,
                                'status' => $reclamacion->status,
                                'reason' => $reclamacion->reason,
                                'resolved_by' => $reclamacion->resolved_by,
                                'resolution_notes' => $reclamacion->resolution_notes,
                                'created_at' => $reclamacion->created_at?->toIso8601String(),
                            ],
                            /*
                             * La misma condición exacta que `CreateCustomerReturnRequestUseCase`
                             * comprueba: solo una reclamación VIVA bloquea. Una rechazada no,
                             * porque el comprador puede volver a reclamar el mismo artículo
                             * --con otra explicación-- mientras siga en plazo. Poner aquí
                             * `=== null` sería más estricto que el backend, y esconder un botón
                             * que sí funciona es la misma clase de mentira que enseñar uno que no.
                             */
                            'can_claim' => $enPlazo
                                && ($reclamacion === null || ! $reclamacion->bloqueaNueva()),
                        ];
                    })
                    ->values()
                    ->all(),
                /*
                 * `can_confirm` lo resuelve el SERVIDOR, no la pantalla.
                 *
                 * Es la convención de la casa y aquí importa especialmente: la misma regla la
                 * aplica `ConfirmOrderDeliveryUseCase` al recibir la confirmación. Si la
                 * pantalla llegara a su propia conclusión, enseñaría un botón que el backend
                 * rechaza --o escondería uno que sí funcionaba--.
                 */
                'delivery' => $entrega === null ? null : [
                    'declared_delivered_at' => $entrega->declared_delivered_at?->toIso8601String(),
                    'confirmed_at' => $entrega->confirmed_at?->toIso8601String(),
                    'released_at' => $entrega->released_at?->toIso8601String(),
                    'can_confirm' => $entrega->declared_delivered_at !== null
                        && $entrega->released_at === null,
                ],
            ];
        })->all();
    }
}
