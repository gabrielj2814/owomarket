<?php

declare(strict_types=1);

namespace Src\CentralMarketplace\Application\UseCases;

use Src\Order\Infrastructure\Eloquent\Models\CentralOrder;
use Src\Order\Infrastructure\Eloquent\Models\CentralOrderItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Src\Customer\Infrastructure\Eloquent\Models\Customer;
use Src\Monetization\Application\UseCases\CalculateAndRecordOrderCommissionUseCase;
use Src\Order\Application\DTOs\CreateOrderData;
use Src\Order\Application\DTOs\OrderItemInputData;
use Src\Order\Application\UseCases\CreateOrderUseCase;
use Src\Tenant\Infrastructure\Eloquent\Models\Tenant;

final class DispatchCentralOrderToTenantsUseCase
{
    public function __construct(
        private readonly CreateOrderUseCase $createOrderUseCase,
        private readonly CalculateAndRecordOrderCommissionUseCase $recordCommissionUseCase
    ) {}

    /**
     * @param CentralOrder $centralOrder
     * @return array<string, string> Map of tenant_id => tenant_order_id
     */
    public function execute(CentralOrder $centralOrder): array
    {
        $items = $centralOrder->items;
        $itemsByTenant = $items->groupBy('tenant_id');
        $dispatchedOrders = [];

        foreach ($itemsByTenant as $tenantId => $tenantItems) {
            $tenant = Tenant::find($tenantId);
            if (! $tenant) {
                continue;
            }

            try {
                tenancy()->initialize($tenant);

                // 1. Find or create Customer in Tenant DB
                $customer = null;
                if ($centralOrder->customer_id) {
                    $customer = Customer::where('central_uuid', $centralOrder->customer_id)->first();
                }
                if (! $customer) {
                    $customer = Customer::where('email', $centralOrder->customer_email)->first();
                }

                if (! $customer) {
                    $customer = Customer::create([
                        'id' => (string) Str::uuid(),
                        'name' => $centralOrder->customer_name,
                        'email' => $centralOrder->customer_email,
                        'phone' => $centralOrder->customer_phone,
                        'document_id' => $centralOrder->customer_document_id,
                        'central_uuid' => $centralOrder->customer_id,
                        'is_active' => true,
                    ]);
                } else if ($centralOrder->customer_id && ! $customer->central_uuid) {
                    $customer->central_uuid = $centralOrder->customer_id;
                    $customer->save();
                }

                // 2. Prepare Order Items DTO for this Tenant
                $orderItemsDto = [];
                $tenantSubtotal = 0.0;

                foreach ($tenantItems as $item) {
                    $itemTotal = (float) $item->price * (int) $item->quantity;
                    $tenantSubtotal += $itemTotal;

                    $orderItemsDto[] = new OrderItemInputData(
                        productId: $item->product_id,
                        productName: $item->product_name,
                        sku: $item->sku ?? 'SKU-'.$item->product_id,
                        price: (float) $item->price,
                        quantity: (int) $item->quantity,
                        attributes: $item->attributes
                    );
                }

                $tenantOrderNumber = 'ORD-'.date('Ymd').'-'.strtoupper(Str::random(6));

                $dto = new CreateOrderData(
                    customerId: (string) $customer->id,
                    paymentMethod: $centralOrder->payment_method,
                    items: $orderItemsDto,
                    taxAmount: 0.0,
                    shippingAmount: 0.0,
                    discountAmount: 0.0,
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
                    ]
                );

                $tenantOrder = $this->createOrderUseCase->execute($dto);
                $tenantOrderId = $tenantOrder->id()->value();
                $tenantOrderTotal = $tenantOrder->total()->amount();

                // 3. Record Payment in Tenant Payments table
                try {
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
                } catch (\Throwable) {
                    // Payment record resilient
                }

                // 4. Update CentralOrderItem tenant_order_id
                foreach ($tenantItems as $item) {
                    $item->tenant_order_id = $tenantOrderId;
                    $item->save();
                }

                $dispatchedOrders[$tenantId] = $tenantOrderId;
            } finally {
                if (tenancy()->initialized) {
                    tenancy()->end();
                }
            }

            // 5. Record Platform Commission in Central DB for this tenant order
            try {
                $commission = $this->recordCommissionUseCase->execute(
                    tenantId: (string) $tenantId,
                    orderId: $tenantOrderId,
                    orderNumber: $tenantOrderNumber,
                    orderTotal: (float) $tenantOrderTotal,
                    paymentGateway: $centralOrder->payment_method,
                    currency: $centralOrder->currency ?? 'USD',
                    metadata: [
                        'central_order_id' => $centralOrder->id,
                        'central_order_number' => $centralOrder->order_number,
                        'source' => 'central_marketplace',
                    ]
                );

                // Update commission info in CentralOrderItem records
                foreach ($tenantItems as $item) {
                    $item->commission_rate = $commission->commission_rate;
                    $item->commission_amount = round(($item->total * ($commission->commission_rate / 100)), 2);
                    $item->save();
                }
            } catch (\Throwable) {
                // Commission calculation resilient
            }
        }

        return $dispatchedOrders;
    }
}
