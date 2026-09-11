<?php

declare(strict_types=1);

namespace Src\Monetization\Application\UseCases;

use Src\Monetization\Infrastructure\Eloquent\Models\OrderDeliveryConfirmation;

/**
 * El expediente de entrega, tal como se le enseña a quien lo mira (subsistema 3, fase B).
 *
 * Un solo caso de uso para el comprador y para el comerciante: el expediente es el mismo
 * documento y ninguno de los dos ve nada que el otro no pueda ver. Es deliberado --la
 * evidencia solo sirve para zanjar una discusion si las dos partes la tienen delante-- y
 * ademas evita dos proyecciones del mismo dato que acaben diciendo cosas distintas.
 *
 * El filtro por comprador lo pone quien llama: este caso de uso no autoriza, proyecta.
 */
final class GetOrderDeliveryStatusUseCase
{
    /**
     * @return array<string, mixed>|null Nulo si la tienda no ha registrado nada todavia.
     */
    public function execute(string $orderId): ?array
    {
        $expediente = OrderDeliveryConfirmation::where('order_id', $orderId)->first();

        if ($expediente === null) {
            return null;
        }

        return [
            'order_id' => $expediente->order_id,
            'shipment_evidence' => $expediente->shipment_evidence ?? [],
            'confirmation_evidence' => $expediente->confirmation_evidence ?? [],
            'declared_delivered_at' => $expediente->declared_delivered_at?->toIso8601String(),
            'confirmed_at' => $expediente->confirmed_at?->toIso8601String(),
            'released_at' => $expediente->released_at?->toIso8601String(),
            'released_by' => $expediente->released_by,
            // Lo unico que la pantalla necesita decidir: si pintar el boton de confirmar.
            // Calculado aqui y no en el frontend para que las dos pantallas --la del
            // comprador y la de la tienda-- no lleguen a conclusiones distintas sobre el
            // mismo expediente.
            'can_confirm' => $expediente->declared_delivered_at !== null
                && $expediente->released_at === null,
        ];
    }
}
