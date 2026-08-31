<?php

declare(strict_types=1);

namespace Src\CentralMarketplace\Application\UseCases;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Src\CentralMarketplace\Application\Service\CentralOrderProrator;
use Src\CentralMarketplace\Infrastructure\Eloquent\Models\CentralOrderDispatch;
use Src\Customer\Infrastructure\Eloquent\Models\Customer;
use Src\Marketplace\Application\Service\CouponRedeemer;
use Src\Marketplace\Application\Service\StockReserver;
use Src\Monetization\Application\UseCases\CalculateAndRecordOrderCommissionUseCase;
use Src\Order\Application\DTOs\CreateOrderData;
use Src\Order\Application\DTOs\OrderItemInputData;
use Src\Order\Application\UseCases\CreateOrderUseCase;
use Src\Order\Infrastructure\Eloquent\Models\CentralOrder;
use Src\Tenant\Infrastructure\Eloquent\Models\Tenant;
use Throwable;

final class DispatchCentralOrderToTenantsUseCase
{
    /**
     * Tope de intentos por tienda (hallazgo N17). Pasado este número, la fila se queda
     * en `failed` de forma definitiva y hace falta intervención manual: seguir
     * reintentando contra una tienda rota sólo llena la cola de trabajo inútil.
     */
    public const MAX_DISPATCH_ATTEMPTS = 5;

    public function __construct(
        private readonly CreateOrderUseCase $createOrderUseCase,
        private readonly CalculateAndRecordOrderCommissionUseCase $recordCommissionUseCase,
        private readonly CentralOrderProrator $prorator,
        private readonly StockReserver $stockReserver,
        private readonly CouponRedeemer $couponRedeemer
    ) {}

    /**
     * Despacha el pedido central a cada tienda participante.
     *
     * Hallazgo C2 — antes este método no tenía transacción ni idempotencia, y
     * el bucle usaba `try { ... } finally { ... }` SIN `catch`. Si la base de
     * datos de una tienda fallaba, las anteriores ya tenían pedido, `payment`
     * y comisión; el cliente reintentaba y se cobraba dos veces.
     *
     * Ahora:
     *   - Cada tienda se reserva con una fila en `central_order_dispatches`,
     *     cuyo índice único `(central_order_id, tenant_id)` garantiza que un
     *     reintento no cree un segundo pedido en la misma tienda.
     *   - El pedido de la tienda, su pago y su marca de despacho se escriben
     *     dentro de una transacción; si algo falla, no queda nada a medias.
     *   - El fallo de una tienda NO aborta las demás, pero queda registrado
     *     con su mensaje de error en lugar de desaparecer en silencio.
     *
     * Hallazgo D1 — el envío y el descuento se reparten entre las tiendas en
     * proporción a su subtotal, en vez de perderse (ver CentralOrderProrator).
     *
     * @return array<string, string> Mapa de tenant_id => tenant_order_id
     */
    public function execute(CentralOrder $centralOrder): array
    {
        $itemsByTenant = $centralOrder->items->groupBy('tenant_id');
        $dispatchedOrders = [];

        // Subtotal bruto por tienda, base del prorrateo.
        $subtotalsByTenant = [];
        foreach ($itemsByTenant as $tenantId => $tenantItems) {
            $subtotalsByTenant[(string) $tenantId] = round(
                $tenantItems->sum(fn ($item) => (float) $item->price * (int) $item->quantity),
                2
            );
        }

        // Hallazgos N34 y N28: desde que cada tienda calcula su propio envio, impuestos y
        // cupon, el pedido central guarda ese desglose y aqui se reparte EXACTAMENTE lo
        // que calculo cada una. Prorratear un total global repartia mal: si la tienda A
        // cobra envio caro y la B lo tiene gratis, el prorrateo por subtotal le cargaba
        // parte del envio de A a la B.
        //
        // El prorrateo se conserva como respaldo para los pedidos creados antes de este
        // cambio, que no llevan desglose (hallazgo D1).
        $desglose = $centralOrder->metadata['charges_by_tenant'] ?? null;

        if (is_array($desglose) && $desglose !== []) {
            $prorated = [];
            foreach ($subtotalsByTenant as $tid => $_) {
                $prorated[$tid] = [
                    'shipping' => (float) ($desglose[$tid]['shipping'] ?? 0.0),
                    'discount' => (float) ($desglose[$tid]['discount'] ?? 0.0),
                    'tax' => (float) ($desglose[$tid]['tax'] ?? 0.0),
                    'coupon_code' => $desglose[$tid]['coupon_code'] ?? null,
                ];
            }
        } else {
            $prorated = $this->prorator->split(
                $subtotalsByTenant,
                (float) ($centralOrder->shipping_amount ?? 0.0),
                (float) ($centralOrder->discount_amount ?? 0.0)
            );
        }

        foreach ($itemsByTenant as $tenantId => $tenantItems) {
            $tenantId = (string) $tenantId;

            $tenant = Tenant::find($tenantId);
            if (! $tenant) {
                continue;
            }

            // 1. Reserva idempotente. Si ya existe una fila para este
            //    (pedido central, tienda), este despacho ya se hizo o está en
            //    curso: no se repite.
            $dispatch = $this->reserveDispatch($centralOrder->id, $tenantId);

            if ($dispatch === null) {
                $existing = CentralOrderDispatch::where('central_order_id', $centralOrder->id)
                    ->where('tenant_id', $tenantId)
                    ->first();

                if ($existing?->tenant_order_id) {
                    $dispatchedOrders[$tenantId] = $existing->tenant_order_id;
                }

                continue;
            }

            $tenantOrderId = null;
            $tenantOrderNumber = null;
            $tenantOrderTotal = null;

            try {
                tenancy()->initialize($tenant);

                $shipping = $prorated[$tenantId]['shipping'] ?? 0.0;
                $discount = $prorated[$tenantId]['discount'] ?? 0.0;
                $tax = $prorated[$tenantId]['tax'] ?? 0.0;
                $couponCode = $prorated[$tenantId]['coupon_code'] ?? null;

                DB::transaction(function () use (
                    $centralOrder, $tenantItems, $shipping, $discount, $tax, $couponCode,
                    &$tenantOrderId, &$tenantOrderNumber, &$tenantOrderTotal
                ) {
                    $customer = $this->resolveTenantCustomer($centralOrder);

                    $orderItemsDto = [];
                    foreach ($tenantItems as $item) {
                        $orderItemsDto[] = new OrderItemInputData(
                            productId: $item->product_id,
                            productName: $item->product_name,
                            sku: $item->sku ?? 'SKU-'.$item->product_id,
                            price: (float) $item->price,
                            quantity: (int) $item->quantity,
                            // Hallazgo N36: la variante viaja hasta el pedido de la tienda,
                            // que es lo que el comerciante mira para preparar el envio.
                            productVariantId: $item->variant_id,
                            attributes: $item->attributes
                        );

                        // Hallazgo N14: **el checkout central no reservaba stock en
                        // absoluto**. Creaba pedidos de tienda sin tocar el inventario, asi
                        // que todo el trabajo de bloqueos de la Fase 1.3 solo protegia el
                        // storefront de cada tienda: se podian vender por el marketplace
                        // unidades que no existian, y el stock nunca bajaba.
                        //
                        // Corre dentro de la transaccion del despacho y con la tenancy ya
                        // inicializada, que es lo que hace efectivo el `lockForUpdate`.
                        //
                        // Hallazgo N36: la variante ya viaja. Antes iba `null` siempre, y
                        // eso descontaba del producto padre, cuyo `quantity` no lo mantiene
                        // nadie en un producto con variantes —`StockReserver` solo toca la
                        // variante cuando se le pasa una—, asi que vender por el
                        // marketplace descuadraba padre y variantes.
                        $this->stockReserver->reserve(
                            (string) $item->product_id,
                            $item->variant_id !== null ? (string) $item->variant_id : null,
                            (int) $item->quantity,
                            (string) $item->product_name
                        );
                    }

                    // Hallazgo N28: el cupon de la tienda se consume aqui, dentro de la
                    // transaccion del despacho y con la tenancy inicializada — los cupones
                    // viven en la base del inquilino. Si el despacho falla, el uso no queda
                    // gastado, igual que en el checkout del storefront (hallazgo C6).
                    if ($couponCode !== null && $couponCode !== '') {
                        $this->couponRedeemer->redeem($couponCode);
                    }

                    $tenantOrderNumber = 'ORD-'.date('Ymd').'-'.strtoupper(Str::random(6));

                    $dto = new CreateOrderData(
                        customerId: (string) $customer->id,
                        paymentMethod: $centralOrder->payment_method,
                        items: $orderItemsDto,
                        // Hallazgo N34: el impuesto lo calculo la propia tienda con sus
                        // tarifas; antes iba fijo a 0.0.
                        taxAmount: $tax,
                        // Hallazgo D1: parte proporcional, ya no 0.0.
                        shippingAmount: $shipping,
                        discountAmount: $discount,
                        orderNumber: $tenantOrderNumber,
                        currency: $centralOrder->currency ?? 'USD',
                        shippingMethod: 'standard',
                        notes: "Pedido despachado desde Marketplace Central OwOMarket #{$centralOrder->order_number}",
                        customerNote: (string) ($centralOrder->shipping_address['notes'] ?? ''),
                        metadata: [
                            'source' => 'central_marketplace',
                            'central_order_id' => $centralOrder->id,
                            'central_order_number' => $centralOrder->order_number,
                            'shipping_address' => $centralOrder->shipping_address,
                            'payment_details' => $centralOrder->payment_details,
                            'prorated_shipping' => $shipping,
                            'prorated_discount' => $discount,
                        ]
                    );

                    $tenantOrder = $this->createOrderUseCase->execute($dto);
                    $tenantOrderId = $tenantOrder->id()->value();
                    $tenantOrderTotal = $tenantOrder->total()->amount();

                    // Fase 4: el pedido de la tienda HEREDA la tasa del pedido central, no
                    // captura la suya. El comprador hizo UN solo pago a una sola tasa; si cada
                    // tienda cogiera la del momento en que corrio este job --que puede ser
                    // horas despues, o un reintento al dia siguiente--, la suma de los
                    // bolivares de las tiendas no cuadraria con lo que el cliente pago.
                    if ($centralOrder->exchange_rate !== null) {
                        DB::table('orders')
                            ->where('id', $tenantOrderId)
                            ->update(['exchange_rate' => $centralOrder->exchange_rate]);
                    }

                    // Pago por el importe REALMENTE imputable a esta tienda.
                    // Antes se registraba el subtotal bruto, así que la suma de
                    // los `payments` no cuadraba con lo que pagó el cliente.
                    $txId = $centralOrder->payment_details['transaction_hash']
                        ?? $centralOrder->payment_details['reference_number']
                        ?? ('TX-'.strtoupper(Str::random(10)));

                    DB::table('payments')->insert([
                        'id' => (string) Str::uuid(),
                        'order_id' => $tenantOrderId,
                        'payment_gateway' => $centralOrder->payment_method,
                        'transaction_id' => (string) $txId,
                        'amount' => $tenantOrderTotal,
                        'fee' => 0.0,
                        'status' => $centralOrder->payment_status === 'paid' ? 'completed' : 'pending',
                        'currency' => $centralOrder->currency ?? 'USD',
                        'gateway_response' => json_encode([
                            'gateway' => $centralOrder->payment_method,
                            'payment_details' => $centralOrder->payment_details,
                            'central_order_id' => $centralOrder->id,
                            'created_at' => now()->toIso8601String(),
                        ]),
                        'paid_at' => $centralOrder->payment_status === 'paid' ? now() : null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                });
            } catch (Throwable $e) {
                // El fallo de una tienda ya no desaparece en silencio ni deja
                // el pedido central en un estado imposible de auditar.
                $dispatch->update([
                    'status' => 'failed',
                    'error_message' => mb_substr($e->getMessage(), 0, 1000),
                ]);

                Log::error('Fallo al despachar pedido central a la tienda', [
                    'central_order_id' => $centralOrder->id,
                    'tenant_id' => $tenantId,
                    'error' => $e->getMessage(),
                ]);

                continue;
            } finally {
                if (tenancy()->initialized) {
                    tenancy()->end();
                }
            }

            $dispatch->update([
                'tenant_order_id' => $tenantOrderId,
                'status' => 'dispatched',
                'dispatched_at' => now(),
            ]);

            foreach ($tenantItems as $item) {
                $item->tenant_order_id = $tenantOrderId;
                $item->save();
            }

            $dispatchedOrders[$tenantId] = $tenantOrderId;

            $this->recordCommission(
                $centralOrder,
                $tenantId,
                $tenantItems,
                (string) $tenantOrderId,
                (string) $tenantOrderNumber,
                $subtotalsByTenant[$tenantId] ?? 0.0,
                $prorated[$tenantId]['discount'] ?? 0.0
            );
        }

        return $dispatchedOrders;
    }

    /**
     * Inserta la reserva de despacho, o reclama la de un intento fallido.
     *
     * Devuelve null cuando esta tienda ya está despachada o en curso por otro
     * proceso. La exclusividad la garantiza el índice único de la tabla, no una
     * lectura previa: dos procesos simultáneos no pueden ganar los dos.
     *
     * **Hallazgo N17.** Antes, el `catch` devolvía null sin mirar el estado de la
     * fila existente, así que una tienda que hubiera quedado en `failed` se saltaba
     * para siempre: aunque alguien reintentara el despacho, esa tienda no volvía a
     * intentarse nunca. Poner el reintento en cola sin arreglar esto habría dado
     * una cola que no reintentaba nada.
     *
     * El reclamo es un UPDATE condicionado a `status = 'failed'`, y se decide por
     * las filas afectadas: si dos trabajadores concurrentes intentan reclamar la
     * misma fila, sólo uno ve un 1 y el otro se retira.
     */
    private function reserveDispatch(string $centralOrderId, string $tenantId): ?CentralOrderDispatch
    {
        try {
            return CentralOrderDispatch::create([
                'id' => (string) Str::uuid(),
                'central_order_id' => $centralOrderId,
                'tenant_id' => $tenantId,
                'status' => 'pending',
            ]);
        } catch (Throwable) {
            // Ya hay fila para (pedido, tienda). Sólo se reclama si quedó fallida y
            // no ha agotado el tope: una tienda permanentemente rota no puede
            // reintentarse eternamente y bloquear la cola.
            $reclamadas = CentralOrderDispatch::query()
                ->where('central_order_id', $centralOrderId)
                ->where('tenant_id', $tenantId)
                ->where('status', 'failed')
                ->where('attempts', '<', self::MAX_DISPATCH_ATTEMPTS)
                ->update([
                    'status' => 'pending',
                    'error_message' => null,
                    'attempts' => DB::raw('attempts + 1'),
                    'updated_at' => now(),
                ]);

            if ($reclamadas === 0) {
                return null;
            }

            return CentralOrderDispatch::query()
                ->where('central_order_id', $centralOrderId)
                ->where('tenant_id', $tenantId)
                ->first();
        }
    }

    private function resolveTenantCustomer(CentralOrder $centralOrder): Customer
    {
        $customer = null;

        if ($centralOrder->customer_id) {
            $customer = Customer::where('central_uuid', $centralOrder->customer_id)->first();
        }

        if (! $customer) {
            $customer = Customer::where('email', $centralOrder->customer_email)->first();
        }

        if (! $customer) {
            return Customer::create([
                'id' => (string) Str::uuid(),
                'name' => $centralOrder->customer_name,
                'email' => $centralOrder->customer_email,
                'phone' => $centralOrder->customer_phone,
                'document_id' => $centralOrder->customer_document_id,
                'central_uuid' => $centralOrder->customer_id,
                'is_active' => true,
            ]);
        }

        if ($centralOrder->customer_id && ! $customer->central_uuid) {
            $customer->central_uuid = $centralOrder->customer_id;
            $customer->save();
        }

        return $customer;
    }

    /**
     * Registra la comisión de la plataforma sobre la venta de esta tienda.
     *
     * ⚠️ BASE DE CÁLCULO — DECISIÓN DE NEGOCIO EXPLÍCITA (hallazgo D1).
     *
     * La comisión se cobra sobre la **mercancía neta de descuento, sin incluir
     * el envío**:
     *
     *     base = subtotal de la tienda − descuento prorrateado
     *
     * El razonamiento: el envío no es ingreso del comerciante (lo absorbe el
     * transportista), y un descuento reduce lo que el comerciante cobra de
     * verdad. Cobrar sobre el bruto —como se hacía antes— significa cobrarle
     * comisión por dinero que nunca recibió.
     *
     * Con el ejemplo de la auditoría (A=$60, B=$40, envío $10, cupón −$30) al
     * 8%: base A = 60 − 18 = $42 → $3,36; base B = 40 − 12 = $28 → $2,24;
     * total $5,60, que es exactamente lo que la auditoría señala como correcto
     * frente a los $8,00 que se cobraban antes.
     *
     * Si el negocio prefiere otra base, este es el único punto a cambiar.
     */
    private function recordCommission(
        CentralOrder $centralOrder,
        string $tenantId,
        $tenantItems,
        string $tenantOrderId,
        string $tenantOrderNumber,
        float $tenantSubtotal,
        float $proratedDiscount
    ): void {
        try {
            $commissionBase = max(0.0, round($tenantSubtotal - $proratedDiscount, 2));

            $commission = $this->recordCommissionUseCase->execute(
                tenantId: $tenantId,
                orderId: $tenantOrderId,
                orderNumber: $tenantOrderNumber,
                orderTotal: $commissionBase,
                paymentGateway: $centralOrder->payment_method,
                currency: $centralOrder->currency ?? 'USD',
                // Hallazgo N15: la comision solo nace cobrable si el pedido central ya
                // esta pagado. Con pago movil, transferencia manual o contra entrega no lo
                // esta, asi que queda en `awaiting_payment` hasta que alguien confirme el
                // cobro — antes se cobraba igual una comision por una venta inexistente.
                paid: ($centralOrder->payment_status ?? 'pending') === 'paid',
                metadata: [
                    'central_order_id' => $centralOrder->id,
                    'central_order_number' => $centralOrder->order_number,
                    'source' => 'central_marketplace',
                    'commission_base' => 'merchandise_net_of_discount',
                    'tenant_subtotal' => $tenantSubtotal,
                    'prorated_discount' => $proratedDiscount,
                ],
                // Hallazgo Auditoria #1: el metadato ya llevaba este id, pero ninguna
                // relacion lo miraba. Ahora va tambien a su columna.
                centralOrderId: (string) $centralOrder->id
            );

            // Reparto de la comisión entre las líneas, con el mismo método del
            // resto mayor: antes se recalculaba ítem a ítem y no cuadraba con
            // la comisión oficial (tres ítems de $3,33 al 8% daban $0,81 por
            // ítems frente a $0,80 registrados).
            $this->spreadCommissionAcrossItems($tenantItems, $commission->commission_rate, (float) $commission->commission_amount);
        } catch (Throwable $e) {
            Log::error('Fallo al registrar la comisión del pedido central', [
                'central_order_id' => $centralOrder->id,
                'tenant_id' => $tenantId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function spreadCommissionAcrossItems($tenantItems, float $rate, float $totalCommission): void
    {
        $weights = [];
        foreach ($tenantItems as $item) {
            $weights[(string) $item->id] = (float) $item->total;
        }

        $shares = $this->prorator->distribute($weights, $totalCommission);

        foreach ($tenantItems as $item) {
            $item->commission_rate = $rate;
            $item->commission_amount = $shares[(string) $item->id] ?? 0.0;
            $item->save();
        }
    }
}
