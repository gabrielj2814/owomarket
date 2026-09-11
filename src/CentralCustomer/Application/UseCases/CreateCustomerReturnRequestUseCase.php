<?php

declare(strict_types=1);

namespace Src\CentralCustomer\Application\UseCases;

use Exception;
use Illuminate\Support\Str;
use Src\CentralCustomer\Infrastructure\Eloquent\Models\CustomerReturnRequest;
use Src\Monetization\Infrastructure\Eloquent\Models\OrderDeliveryConfirmation;
use Src\Order\Infrastructure\Eloquent\Models\CentralOrder;

final class CreateCustomerReturnRequestUseCase
{
    /**
     * @param  array{order_id: string, product_id: string, reason: string, description: string, photos?: array<int, string>|null}  $data
     */
    public function execute(string $customerId, array $data): CustomerReturnRequest
    {
        $order = CentralOrder::with('items')
            ->where('id', $data['order_id'])
            ->where('customer_id', $customerId)
            ->first();

        if (! $order) {
            throw new Exception('El pedido no fue encontrado o no pertenece a tu cuenta.', 404);
        }

        $item = $order->items->firstWhere('product_id', $data['product_id']);
        if (! $item) {
            throw new Exception('El producto seleccionado no pertenece a este pedido.', 422);
        }

        $existing = CustomerReturnRequest::where('order_id', $order->id)
            ->where('product_id', $data['product_id'])
            ->whereIn('status', ['requested', 'in_review', 'approved'])
            ->first();

        if ($existing) {
            throw new Exception('Ya existe una solicitud de devolución activa para este producto.', 422);
        }

        return CustomerReturnRequest::create([
            'id' => (string) Str::uuid(),
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            /*
             * Subsistema 5: la columna que conecta la reclamacion con el dinero.
             *
             * Las reclamaciones se indexan por pedido CENTRAL mas producto, pero la comision
             * vive por pedido DE TIENDA. Sin capturarlo aqui --que es cuando el articulo
             * todavia esta a mano-- aprobar la reclamacion no sabria que comision revertir, o
             * peor, revertiria la de otra tienda del mismo carrito.
             */
            'tenant_order_id' => $item->tenant_order_id,
            // Medicion: lo que de verdad se reclama. Sin importe no se puede dimensionar nada
            // --ni el fondo, ni el tope de cobertura, ni el techo mensual de alarma--.
            'amount' => (float) $item->total,
            'delivered_at' => $this->entregadoEl($item->tenant_order_id),
            'customer_id' => $customerId,
            'customer_email' => $order->customer_email,
            'product_id' => $item->product_id,
            'product_name' => $item->product_name,
            'tenant_id' => $item->tenant_id,
            'reason' => trim($data['reason']),
            'description' => trim($data['description']),
            'photos' => $data['photos'] ?? [],
            'status' => 'requested',
        ]);
    }

    /**
     * Cuando se dio por recibido el pedido, si consta.
     *
     * Medicion: es lo unico que dira **cuantos dias despues de recibir** llega una
     * reclamacion, y por tanto si los 60 dias de retencion del fondo son los correctos.
     * Sale del expediente de entrega del subsistema 3, que es quien lo sabe.
     */
    private function entregadoEl(?string $tenantOrderId): ?string
    {
        if ($tenantOrderId === null) {
            return null;
        }

        $expediente = OrderDeliveryConfirmation::where('order_id', $tenantOrderId)->first();

        return $expediente?->released_at?->toDateTimeString()
            ?? $expediente?->declared_delivered_at?->toDateTimeString();
    }
}
