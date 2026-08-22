<?php

declare(strict_types=1);

namespace Src\CentralMarketplace\Application\UseCases;

use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Src\CentralMarketplace\Application\Service\CentralItemPriceResolver;
use Src\Order\Infrastructure\Eloquent\Models\CentralOrder;
use Src\Order\Infrastructure\Eloquent\Models\CentralOrderItem;

final class CreateUnifiedCentralOrderUseCase
{
    public function __construct(
        private readonly DispatchCentralOrderToTenantsUseCase $dispatchUseCase,
        private readonly CentralItemPriceResolver $priceResolver
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public function execute(array $payload): CentralOrder
    {
        $customer = $payload['customer'] ?? [];
        $shippingAddress = $payload['shipping_address'] ?? [];
        $items = $payload['items'] ?? [];

        if (empty($items)) {
            throw new Exception('El carrito no contiene productos.', 422);
        }

        // Hallazgo C2 — idempotencia del checkout. Si el cliente reintenta
        // (porque el despacho a una tienda falló, o porque pulsó dos veces)
        // con la misma clave, se devuelve el pedido que ya existe en lugar de
        // crear otro. Antes cada reintento generaba un CentralOrder completo
        // y la tienda que sí había respondido acababa con dos pedidos y dos
        // comisiones por una sola compra.
        $idempotencyKey = isset($payload['idempotency_key']) && trim((string) $payload['idempotency_key']) !== ''
            ? trim((string) $payload['idempotency_key'])
            : null;

        if ($idempotencyKey !== null) {
            $existing = CentralOrder::where('idempotency_key', $idempotencyKey)->first();

            if ($existing) {
                // Se reintenta el despacho: es idempotente por tienda, así que
                // sólo alcanzará a las que quedaron pendientes.
                $existing->load(['items', 'customer']);
                $this->dispatchUseCase->execute($existing);

                return $existing->fresh(['items', 'customer']);
            }
        }

        $orderNumber = 'OWO-'.date('Ymd').'-'.strtoupper(Str::random(6));

        // Hallazgo B1: el precio, el nombre y el SKU de cada línea se resuelven
        // contra `central_products`. Lo que venga en $payload['items'][*]['price']
        // se descarta por completo — antes se sumaba tal cual y bastaba con
        // interceptar el POST del checkout para comprar por $0,01.
        $resolvedItems = [];
        $subtotal = 0.0;

        foreach ($items as $item) {
            $resolved = $this->priceResolver->resolve($item);
            $resolvedItems[] = $resolved;
            $subtotal += $resolved['price'] * $resolved['quantity'];
        }

        $subtotal = round($subtotal, 2);

        $shippingAmount = (float) ($payload['shipping_amount'] ?? 0.0);
        $discountAmount = (float) ($payload['discount_amount'] ?? 0.0);
        $total = max(0.0, $subtotal + $shippingAmount - $discountAmount);

        // Hallazgo C2: la creación del pedido y sus líneas es atómica. Antes no
        // había transacción en ningún nivel, así que un fallo a mitad dejaba un
        // CentralOrder sin líneas —o con parte de ellas— imposible de despachar.
        $centralOrder = DB::transaction(function () use (
            $customer, $shippingAddress, $payload, $orderNumber, $idempotencyKey,
            $subtotal, $shippingAmount, $discountAmount, $total, $resolvedItems
        ) {
            $order = CentralOrder::create([
                'id' => (string) Str::uuid(),
                'order_number' => $orderNumber,
                'idempotency_key' => $idempotencyKey,
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

            // Create CentralOrderItem records — a partir de los datos ya resueltos
            // contra el catálogo, no de los que envió el navegador.
            foreach ($resolvedItems as $it) {
                CentralOrderItem::create([
                    'id' => (string) Str::uuid(),
                    'central_order_id' => $order->id,
                    'tenant_id' => $it['tenant_id'],
                    'product_id' => $it['product_id'],
                    'product_name' => $it['name'],
                    'sku' => $it['sku'],
                    'price' => $it['price'],
                    'quantity' => $it['quantity'],
                    'total' => round($it['price'] * $it['quantity'], 2),
                    'attributes' => $it['attributes'],
                ]);
            }

            return $order;
        });

        // El despacho va FUERA de la transacción a propósito: escribe en las
        // bases de datos de las tiendas (otra conexión), así que englobarlo
        // aquí no daría atomicidad real. Su seguridad viene de ser idempotente
        // por (pedido central, tienda), no de la transacción.
        $centralOrder->load(['items', 'customer']);
        $this->dispatchUseCase->execute($centralOrder);

        return $centralOrder->fresh(['items', 'customer']);
    }
}
