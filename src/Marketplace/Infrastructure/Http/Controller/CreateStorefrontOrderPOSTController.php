<?php

declare(strict_types=1);

namespace Src\Marketplace\Infrastructure\Http\Controller;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Src\Coupon\Infrastructure\Eloquent\Models\Coupon;
use Src\Customer\Infrastructure\Eloquent\Models\Customer;
use Src\Order\Application\DTOs\CreateOrderData;
use Src\Order\Application\DTOs\OrderItemData;
use Src\Order\Application\UseCases\CreateOrderUseCase;
use Src\Product\Infrastructure\Eloquent\Models\Product;
use Src\Product\Infrastructure\Eloquent\Models\ProductVariant;
use Src\Shared\Helper\ApiResponse;

final class CreateStorefrontOrderPOSTController extends Controller
{
    public function __construct(
        private readonly CreateOrderUseCase $createOrderUseCase
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
            'coupon_code' => ['nullable', 'string', 'max:50'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'string'],
            'items.*.product_name' => ['required', 'string'],
            'items.*.sku' => ['required', 'string'],
            'items.*.price' => ['required', 'numeric', 'min:0'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.variant_id' => ['nullable', 'string'],
            'items.*.attributes' => ['nullable', 'array'],
        ]);

        try {
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
                $pId = (string) $item['product_id'];
                $pName = (string) $item['product_name'];
                $sku = (string) $item['sku'];
                $price = (float) $item['price'];
                $qty = (int) $item['quantity'];
                $varId = isset($item['variant_id']) && $item['variant_id'] !== '' ? (string) $item['variant_id'] : null;
                $attrs = isset($item['attributes']) && is_array($item['attributes']) ? $item['attributes'] : null;

                $calculatedSubtotal += ($price * $qty);

                $orderItemsDto[] = new OrderItemData(
                    productId: $pId,
                    productName: $pName,
                    sku: $sku,
                    price: $price,
                    quantity: $qty,
                    productVariantId: $varId,
                    attributes: $attrs
                );

                // Decrement stock if trackable
                try {
                    if ($varId) {
                        $variant = ProductVariant::find($varId);
                        if ($variant && $variant->quantity >= $qty) {
                            $variant->decrement('quantity', $qty);
                        }
                    }
                    $product = Product::find($pId);
                    if ($product && $product->quantity >= $qty) {
                        $product->decrement('quantity', $qty);
                    }
                } catch (\Throwable) {
                }
            }

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

            $metadata = [
                'shipping_address' => $request->input('shipping_address'),
                'customer_info' => $request->input('customer'),
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

            return ApiResponse::success(
                data: [
                    'order_id' => $order->id->value,
                    'order_number' => $order->orderNumber,
                    'total' => $order->total,
                    'redirect_url' => "/order/{$order->id->value}/confirmation",
                ],
                message: '¡Pedido creado exitosamente!',
                code: 201
            );
        } catch (Exception $e) {
            return ApiResponse::error(
                message: $e->getMessage(),
                code: 400
            );
        }
    }
}
