<?php

declare(strict_types=1);

namespace Src\CentralMarketplace\Application\UseCases;

use Src\Order\Infrastructure\Eloquent\Models\CentralOrder;
use Src\Order\Infrastructure\Eloquent\Models\CentralOrderItem;
use Exception;
use Illuminate\Support\Str;

final class CreateUnifiedCentralOrderUseCase
{
    public function __construct(
        private readonly DispatchCentralOrderToTenantsUseCase $dispatchUseCase
    ) {}

    /**
     * @param array<string, mixed> $payload
     * @return CentralOrder
     */
    public function execute(array $payload): CentralOrder
    {
        $customer = $payload['customer'] ?? [];
        $shippingAddress = $payload['shipping_address'] ?? [];
        $items = $payload['items'] ?? [];

        if (empty($items)) {
            throw new Exception('El carrito no contiene productos.', 422);
        }

        $orderNumber = 'OWO-'.date('Ymd').'-'.strtoupper(Str::random(6));

        $subtotal = 0.0;
        foreach ($items as $item) {
            $subtotal += (float) ($item['price'] * (int) ($item['quantity'] ?? 1));
        }

        $shippingAmount = (float) ($payload['shipping_amount'] ?? 0.0);
        $discountAmount = (float) ($payload['discount_amount'] ?? 0.0);
        $total = max(0.0, $subtotal + $shippingAmount - $discountAmount);

        $centralOrder = CentralOrder::create([
            'id' => (string) Str::uuid(),
            'order_number' => $orderNumber,
            'customer_id' => $customer['id'] ?? ($customer['central_uuid'] ?? null),
            'customer_name' => (string) ($customer['name'] ?? 'Cliente Central'),
            'customer_email' => (string) ($customer['email'] ?? ''),
            'customer_phone' => (string) ($customer['phone'] ?? null),
            'customer_document_id' => (string) ($customer['document_id'] ?? null),
            'shipping_address' => $shippingAddress,
            'payment_method' => (string) ($payload['payment_method'] ?? 'pago_movil'),
            'payment_details' => $payload['payment_details'] ?? null,
            'subtotal' => $subtotal,
            'shipping_amount' => $shippingAmount,
            'discount_amount' => $discountAmount,
            'total' => $total,
            'currency' => (string) ($payload['currency'] ?? 'USD'),
            'status' => 'pending',
            'payment_status' => 'pending',
            'metadata' => [
                'coupon_code' => $payload['coupon_code'] ?? null,
                'source' => 'central_marketplace_unified_checkout',
            ],
        ]);

        // Create CentralOrderItem records
        foreach ($items as $it) {
            $itemTotal = (float) $it['price'] * (int) ($it['quantity'] ?? 1);

            CentralOrderItem::create([
                'id' => (string) Str::uuid(),
                'central_order_id' => $centralOrder->id,
                'tenant_id' => (string) $it['tenant_id'],
                'product_id' => (string) $it['product_id'],
                'product_name' => (string) $it['product_name'],
                'sku' => (string) ($it['sku'] ?? null),
                'price' => (float) $it['price'],
                'quantity' => (int) ($it['quantity'] ?? 1),
                'total' => $itemTotal,
                'attributes' => $it['attributes'] ?? null,
            ]);
        }

        // Reload relationships and dispatch to each respective tenant store
        $centralOrder->load(['items', 'customer']);
        $this->dispatchUseCase->execute($centralOrder);

        return $centralOrder->fresh(['items', 'customer']);
    }
}
