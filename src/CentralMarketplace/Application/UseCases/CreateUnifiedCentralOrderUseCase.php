<?php

declare(strict_types=1);

namespace Src\CentralMarketplace\Application\UseCases;

use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Src\CentralMarketplace\Application\Service\CentralItemPriceResolver;
use Src\CentralMarketplace\Application\Service\CentralOrderChargesCalculator;
use Src\CentralMarketplace\Infrastructure\Jobs\DispatchCentralOrderJob;
use Src\ExchangeRate\Application\UseCase\GetActiveExchangeRateUseCase;
use Src\Order\Infrastructure\Eloquent\Models\CentralOrder;
use Src\Order\Infrastructure\Eloquent\Models\CentralOrderItem;
use Throwable;

final class CreateUnifiedCentralOrderUseCase
{
    public function __construct(
        private readonly CentralItemPriceResolver $priceResolver,
        private readonly CentralOrderChargesCalculator $chargesCalculator,
        private readonly GetActiveExchangeRateUseCase $tasaActiva
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
                DispatchCentralOrderJob::dispatch($existing->id);

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

        // Hallazgos N34 y N28: `shipping_amount` y `discount_amount` se tomaban tal cual
        // del navegador —el mismo error que B1 con otro nombre— y el checkout central no
        // incluia ni envio ni impuestos, asi que el total mostrado era el subtotal puro y
        // el importe que el comprador transferia no coincidia con nada.
        //
        // Ahora cada tienda calcula lo suyo con sus propias tarifas y cupones, y el pedido
        // central suma. El desglose se guarda para que el despacho reparta exactamente lo
        // que calculo cada tienda, en vez de prorratear un total global.
        $charges = $this->chargesCalculator->calculate(
            $resolvedItems,
            $shippingAddress,
            is_array($payload['coupons'] ?? null) ? $payload['coupons'] : []
        );

        $shippingAmount = $charges['shipping'];
        $discountAmount = $charges['discount'];
        $taxAmount = $charges['tax'];
        $total = max(0.0, $subtotal + $shippingAmount + $taxAmount - $discountAmount);

        // Hallazgo C2: la creación del pedido y sus líneas es atómica. Antes no
        // había transacción en ningún nivel, así que un fallo a mitad dejaba un
        // CentralOrder sin líneas —o con parte de ellas— imposible de despachar.
        // Fuera de la transaccion: si el BCV no ha sincronizado, el pedido no puede caerse
        // por eso. Queda sin tasa registrada, igual que en la Fase 1 con las comisiones.
        try {
            $tasaDeLaCompra = $this->tasaActiva->execute()->getRate()->value();
        } catch (Throwable) {
            $tasaDeLaCompra = null;
        }

        $centralOrder = DB::transaction(function () use (
            $customer, $shippingAddress, $payload, $orderNumber, $idempotencyKey,
            $subtotal, $shippingAmount, $discountAmount, $taxAmount, $total, $resolvedItems,
            $charges, $tasaDeLaCompra
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
                // Fase 4: la tasa a la que compra este cliente. El precio se pone en dolares
                // pero el paga bolivares, y sin esto esa cifra no quedaba registrada en
                // ninguna parte: existia solo en su extracto bancario.
                'exchange_rate' => $tasaDeLaCompra,
                'status' => 'pending',
                'payment_status' => 'pending',
                'metadata' => [
                    'source' => 'central_marketplace_unified_checkout',
                    // Desglose por tienda: envio, impuestos y cupon que calculo cada una.
                    // El despacho lo lee para repartir exactamente esto (hallazgos N34/N28).
                    'charges_by_tenant' => $charges['by_tenant'],
                    'tax_amount' => $taxAmount,
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
                    // Hallazgo N36: sin esto el comerciante recibia el pedido sin saber
                    // que variante enviar, y el stock se descontaba del padre.
                    'variant_id' => $it['variant_id'] ?? null,
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
        //
        // Hallazgo N17: además va FUERA de la petición. Antes el comprador esperaba a
        // que respondieran todas las tiendas, y la que fallaba se quedaba en `failed`
        // sin que nada la reintentara jamás. Ahora el pedido central ya está guardado
        // —con sus líneas, que es lo que lee la pantalla de confirmación— y la
        // propagación a las tiendas la hace la cola, con reintentos y espera creciente.
        DispatchCentralOrderJob::dispatch($centralOrder->id);

        return $centralOrder->fresh(['items', 'customer']);
    }
}
