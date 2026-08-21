<?php

declare(strict_types=1);

namespace Src\Marketplace\Infrastructure\Http\Controller;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Src\Coupon\Infrastructure\Eloquent\Models\Coupon;
use Src\Customer\Infrastructure\Eloquent\Models\Customer;
use Src\Order\Application\DTOs\CreateOrderData;
use Src\Order\Application\DTOs\OrderItemInputData;
use Src\Marketplace\Application\Service\StockReserver;
use Src\Marketplace\Application\Service\StorefrontItemPriceResolver;
use Src\Order\Application\UseCases\CreateOrderUseCase;
use Src\Shared\Helper\ApiResponse;

final class CreateStorefrontOrderPOSTController extends Controller
{
    public function __construct(
        private readonly CreateOrderUseCase $createOrderUseCase,
        private readonly StorefrontItemPriceResolver $priceResolver,
        private readonly StockReserver $stockReserver
    ) {}

    public function index(Request $request): JsonResponse
    {
        // 1. Validation
        $request->validate([
            'customer.name' => ['required', 'string', 'max:150'],
            'customer.email' => ['required', 'email', 'max:150'],
            'customer.phone' => ['nullable', 'string', 'max:50'],
            'customer.document_id' => ['nullable', 'string', 'max:50'],
            'shipping_address.address' => ['required', 'string', 'max:255'],
            'shipping_address.city' => ['required', 'string', 'max:100'],
            'shipping_address.state' => ['nullable', 'string', 'max:100'],
            'shipping_address.zip' => ['nullable', 'string', 'max:20'],
            'shipping_address.notes' => ['nullable', 'string', 'max:500'],
            'shipping_method' => ['required', 'string', 'max:100'],
            'shipping_amount' => ['nullable', 'numeric', 'min:0'],
            'payment_method' => ['required', 'string', 'max:50'],
            'payment_details' => ['nullable', 'array'],
            'payment_details.bank_origin' => ['nullable', 'string', 'max:100'],
            'payment_details.phone_origin' => ['nullable', 'string', 'max:50'],
            'payment_details.reference_number' => ['nullable', 'string', 'max:100'],
            'payment_details.binance_id' => ['nullable', 'string', 'max:100'],
            'payment_details.transaction_hash' => ['nullable', 'string', 'max:150'],
            'coupon_code' => ['nullable', 'string', 'max:50'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'string'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.variant_id' => ['nullable', 'string'],
            'items.*.attributes' => ['nullable', 'array'],
            // 'price', 'product_name' y 'sku' ya NO se validan ni se usan:
            // se resuelven contra la base de datos (hallazgo B1). El frontend
            // puede seguir enviándolos; simplemente se ignoran.
        ]);

        try {
            // Hallazgo C1: todo el checkout —reserva de stock, consumo del
            // cupón y creación del pedido— ocurre dentro de una transacción.
            // Es lo que hace efectivos los lockForUpdate del StockReserver y lo
            // que garantiza que, si el pedido no llega a crearse, el stock no
            // quede descontado ni el cupón consumido.
            [$orderId, $orderNum, $orderTotal, $paymentMethod] = DB::transaction(function () use ($request) {
            // 2. Customer resolution (find or create in tenant DB)
            $customerEmail = trim((string) $request->input('customer.email'));
            $customerName = trim((string) $request->input('customer.name'));
            $customerPhone = (string) $request->input('customer.phone');
            $customerDoc = (string) $request->input('customer.document_id');

            $customer = Customer::firstOrCreate(
                ['email' => $customerEmail],
                [
                    'id' => Str::uuid()->toString(),
                    'name' => $customerName,
                    'phone' => $customerPhone,
                    'is_active' => true,
                    'accepts_marketing' => true,
                    'metadata' => ['document_id' => $customerDoc],
                ]
            );

            // 3. Process items and calculate subtotal
            $rawItems = $request->input('items', []);
            $orderItemsDto = [];
            $calculatedSubtotal = 0.0;

            foreach ($rawItems as $item) {
                // Hallazgo B1: precio, nombre y SKU salen de la base de datos
                // del inquilino. Lo que venga en $item['price'] se descarta.
                $resolved = $this->priceResolver->resolve($item);

                $pId = $resolved['product_id'];
                $qty = $resolved['quantity'];
                $varId = $resolved['variant_id'];
                $attrs = isset($item['attributes']) && is_array($item['attributes']) ? $item['attributes'] : null;

                $calculatedSubtotal += ($resolved['price'] * $qty);

                $orderItemsDto[] = new OrderItemInputData(
                    productId: $pId,
                    productName: $resolved['name'],
                    sku: $resolved['sku'],
                    price: $resolved['price'],
                    quantity: $qty,
                    productVariantId: $varId,
                    attributes: $attrs
                );

                // Hallazgo C1: la reserva bloquea la fila y falla con 409 si no
                // hay existencias, en vez de crear el pedido igual. Corre
                // dentro de la transacción abierta más abajo, que es lo que
                // hace efectivo el lockForUpdate.
                $this->stockReserver->reserve($pId, $varId, $qty, $resolved['name']);
            }

            $calculatedSubtotal = round($calculatedSubtotal, 2);

            // 4. Calculate discount if coupon provided
            $discountAmount = 0.0;
            $couponCode = trim((string) $request->input('coupon_code', ''));
            if ($couponCode !== '') {
                $coupon = Coupon::where('code', $couponCode)->where('is_active', true)->first();
                if ($coupon) {
                    if ($coupon->type === 'percentage') {
                        $discountAmount = round($calculatedSubtotal * ($coupon->value / 100), 2);
                    } else {
                        $discountAmount = min($calculatedSubtotal, (float) $coupon->value);
                    }
                    $coupon->increment('used_count');
                }
            }

            $shippingAmount = (float) $request->input('shipping_amount', 0.0);
            $taxAmount = 0.0; // Included in price or flat
            $shippingMethod = (string) $request->input('shipping_method', 'standard');
            $paymentMethod = (string) $request->input('payment_method', 'bank_transfer');
            $orderNumber = 'ORD-'.strtoupper(Str::random(8));

            $paymentDetails = $request->input('payment_details', []);

            $metadata = [
                'shipping_address' => $request->input('shipping_address'),
                'customer_info' => $request->input('customer'),
                'payment_details' => $paymentDetails,
                'source' => 'storefront_checkout',
            ];

            // 5. Build DTO and Execute UseCase
            $dto = new CreateOrderData(
                customerId: (string) $customer->id,
                paymentMethod: $paymentMethod,
                items: $orderItemsDto,
                taxAmount: $taxAmount,
                shippingAmount: $shippingAmount,
                discountAmount: $discountAmount,
                orderNumber: $orderNumber,
                currency: 'USD',
                shippingMethod: $shippingMethod,
                notes: 'Pedido realizado desde la tienda web pública',
                customerNote: (string) $request->input('shipping_address.notes', ''),
                metadata: $metadata
            );

            $order = $this->createOrderUseCase->execute($dto);

            $orderId = $order->id()->value();
            $orderNum = $order->orderNumber()->value();
            $orderTotal = $order->total()->amount();

            // 6. Record Payment in payments table.
            //    Ya no lleva `catch (\Throwable)` vacío: si el pago no se puede
            //    registrar, la transacción revierte el pedido entero en vez de
            //    dejar una venta sin rastro de cobro.
            $txId = $paymentDetails['transaction_hash']
                ?? $paymentDetails['reference_number']
                ?? ('TX-'.strtoupper(Str::random(10)));

            DB::table('payments')->insert([
                'id' => (string) Str::uuid(),
                'order_id' => $orderId,
                'payment_gateway' => $paymentMethod,
                'transaction_id' => (string) $txId,
                'amount' => $orderTotal,
                'fee' => 0.0,
                'status' => 'pending',
                'currency' => 'USD',
                'gateway_response' => json_encode([
                    'gateway' => $paymentMethod,
                    'payment_details' => $paymentDetails,
                    'customer' => $request->input('customer'),
                    'created_at' => now()->toIso8601String(),
                ]),
                'paid_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return [$orderId, $orderNum, $orderTotal, $paymentMethod];
            });

            // 7. Calculate and Record Platform Commission in Central DB.
            //    Va FUERA de la transacción a propósito: escribe en la base
            //    central, otra conexión, así que incluirla no daría atomicidad
            //    real. Si falla, el pedido de la tienda es válido igualmente y
            //    lo que queda pendiente es registrar la comisión.
            try {
                $tenantId = (string) (tenant('id') ?? '');
                if ($tenantId !== '') {
                    $commissionUseCase = app(\Src\Monetization\Application\UseCases\CalculateAndRecordOrderCommissionUseCase::class);
                    $commissionUseCase->execute(
                        tenantId: $tenantId,
                        orderId: $orderId,
                        orderNumber: $orderNum,
                        orderTotal: (float) $orderTotal,
                        paymentGateway: $paymentMethod,
                        currency: 'USD',
                        metadata: [
                            'customer_email' => $request->input('customer.email'),
                            'source' => 'storefront_checkout',
                        ]
                    );
                }
            } catch (\Throwable) {
                // Keep order creation robust
            }

            return ApiResponse::success(
                data: [
                    'order_id' => $orderId,
                    'order_number' => $orderNum,
                    'total' => $orderTotal,
                    'redirect_url' => "/order/{$orderId}/confirmation",
                ],
                message: '¡Pedido creado exitosamente!',
                code: 201
            );
        } catch (Exception $e) {
            // Se conserva el código de la excepción: la falta de existencias
            // devuelve 409 (hallazgo C1), no un 400 genérico que el frontend
            // no puede distinguir de un error de validación.
            $code = (int) $e->getCode();

            return ApiResponse::error(
                message: $e->getMessage(),
                code: $code >= 400 && $code < 600 ? $code : 400
            );
        }
    }
}
