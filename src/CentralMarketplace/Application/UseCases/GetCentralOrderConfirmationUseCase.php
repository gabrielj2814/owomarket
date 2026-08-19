<?php

declare(strict_types=1);

namespace Src\CentralMarketplace\Application\UseCases;

use App\Models\CentralOrder;
use Exception;
use Src\Tenant\Infrastructure\Eloquent\Models\Tenant;

final class GetCentralOrderConfirmationUseCase
{
    /**
     * @param string $centralOrderIdOrNumber
     * @return array<string, mixed>
     */
    public function execute(string $centralOrderIdOrNumber): array
    {
        $order = CentralOrder::with(['items', 'customer'])
            ->where('id', $centralOrderIdOrNumber)
            ->orWhere('order_number', $centralOrderIdOrNumber)
            ->first();

        if (! $order) {
            throw new Exception('Pedido no encontrado.', 404);
        }

        // Group items by store / tenant
        $storesBreakdown = [];
        $tenantIds = $order->items->pluck('tenant_id')->unique()->filter()->values();
        $tenants = Tenant::with('domains')->whereIn('id', $tenantIds)->get()->keyBy('id');

        foreach ($order->items->groupBy('tenant_id') as $tenantId => $items) {
            $tenant = $tenants->get($tenantId);
            $storeName = $tenant ? ($tenant->name ?? 'Tienda') : 'Tienda Asociada';
            $storeDomain = $tenant && $tenant->domains->isNotEmpty() ? $tenant->domains->first()->domain : null;

            $itemsData = [];
            $storeSubtotal = 0.0;

            foreach ($items as $item) {
                $storeSubtotal += (float) $item->total;
                $itemsData[] = [
                    'id' => $item->id,
                    'product_id' => $item->product_id,
                    'product_name' => $item->product_name,
                    'sku' => $item->sku,
                    'price' => (float) $item->price,
                    'quantity' => (int) $item->quantity,
                    'total' => (float) $item->total,
                    'tenant_order_id' => $item->tenant_order_id,
                    'commission_amount' => (float) $item->commission_amount,
                ];
            }

            $storesBreakdown[] = [
                'tenant_id' => $tenantId,
                'store_name' => $storeName,
                'store_domain' => $storeDomain,
                'subtotal' => $storeSubtotal,
                'items_count' => count($itemsData),
                'items' => $itemsData,
            ];
        }

        return [
            'id' => $order->id,
            'order_number' => $order->order_number,
            'status' => $order->status,
            'payment_status' => $order->payment_status,
            'payment_method' => $order->payment_method,
            'payment_details' => $order->payment_details,
            'subtotal' => (float) $order->subtotal,
            'shipping_amount' => (float) $order->shipping_amount,
            'discount_amount' => (float) $order->discount_amount,
            'total' => (float) $order->total,
            'currency' => $order->currency,
            'created_at' => $order->created_at?->format('d/m/Y H:i') ?? '',
            'customer' => [
                'name' => $order->customer_name,
                'email' => $order->customer_email,
                'phone' => $order->customer_phone,
            ],
            'shipping_address' => $order->shipping_address,
            'stores_count' => count($storesBreakdown),
            'stores_breakdown' => $storesBreakdown,
        ];
    }
}
