<?php

declare(strict_types=1);

namespace Src\Marketplace\Application\UseCase;

use Illuminate\Support\Facades\DB;
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

        return $pedidos->map(function ($pedido) use ($entregas, $items) {
            $entrega = $entregas->get($pedido->id);

            return [
                'id' => $pedido->id,
                'order_number' => $pedido->order_number,
                'status' => $pedido->status,
                'total' => (float) $pedido->total,
                'currency' => $pedido->currency ?? 'USD',
                'created_at' => $pedido->created_at,
                'items' => collect($items->get($pedido->id, []))
                    ->map(fn ($i) => [
                        'id' => $i->id,
                        'product_id' => $i->product_id,
                        'product_name' => $i->product_name,
                        'quantity' => (int) $i->quantity,
                        'price' => (float) $i->price,
                    ])
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
