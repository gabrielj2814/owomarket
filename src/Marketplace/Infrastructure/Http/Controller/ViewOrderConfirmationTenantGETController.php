<?php

declare(strict_types=1);

namespace Src\Marketplace\Infrastructure\Http\Controller;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Src\Category\Infrastructure\Eloquent\Models\Category;
use Src\Order\Infrastructure\Eloquent\Models\Order;
use Src\Order\Infrastructure\Eloquent\Models\OrderItem;
use Src\TenantSettings\Application\UseCases\GetStoreSettingsUseCase;

final class ViewOrderConfirmationTenantGETController extends Controller
{
    public function __construct(
        private readonly ?GetStoreSettingsUseCase $getStoreSettingsUseCase = null
    ) {}

    public function index(Request $request, string $id): Response
    {
        $host = $request->getHost();

        // 1. Fetch Store Settings
        $storeSettings = [];
        try {
            $useCase = $this->getStoreSettingsUseCase ?? app(GetStoreSettingsUseCase::class);
            $settingsEntity = $useCase->execute();
            $storeSettings = $settingsEntity->toKeyValueMap();
        } catch (\Throwable) {
            $storeSettings = [
                'store_name' => 'Mi Tienda Online',
                'currency' => 'USD',
            ];
        }

        if (empty($storeSettings)) {
            $storeSettings = [
                'store_name' => 'Mi Tienda Online',
                'currency' => 'USD',
            ];
        }

        // 2. Find Order
        $order = Order::query()
            ->with(['items', 'customer'])
            ->where('id', $id)
            ->orWhere('order_number', $id)
            ->first();

        if (! $order) {
            abort(404, 'Orden no encontrada');
        }

        // 3. Format Order Items
        $formattedItems = $order->items->map(function (OrderItem $item) {
            return [
                'id' => (string) $item->id,
                'product_id' => (string) $item->product_id,
                'product_name' => $item->product_name,
                'sku' => $item->sku,
                'price' => (float) $item->price,
                'quantity' => (int) $item->quantity,
                'total' => (float) ($item->price * $item->quantity),
                'attributes' => is_array($item->attributes) ? $item->attributes : null,
            ];
        })->all();

        // 4. Format Order Summary
        $orderDetails = [
            'id' => (string) $order->id,
            'order_number' => $order->order_number,
            'status' => $order->status ?? 'pending',
            'payment_method' => $order->payment_method,
            'payment_status' => $order->payment_status ?? 'pending',
            'shipping_method' => $order->shipping_method,
            'subtotal' => (float) $order->subtotal,
            'tax_amount' => (float) $order->tax_amount,
            'shipping_amount' => (float) $order->shipping_amount,
            'discount_amount' => (float) $order->discount_amount,
            'total' => (float) $order->total,
            'currency' => $order->currency ?? 'USD',
            'created_at' => $order->created_at?->format('d/m/Y H:i') ?? '',
            'customer' => [
                'name' => $order->customer?->name ?? 'Cliente',
                'email' => $order->customer?->email ?? '',
                'phone' => $order->customer?->phone ?? '',
            ],
            'shipping_address' => $order->metadata['shipping_address'] ?? null,
            'items' => $formattedItems,
        ];

        // 5. Fetch Active Categories for Navbar
        $categories = Category::query()
            ->where('is_active', true)
            ->orderBy('position', 'asc')
            ->get()
            ->map(fn (Category $cat) => [
                'id' => (string) $cat->id,
                'name' => $cat->name,
                'slug' => $cat->slug,
                'image' => $cat->image,
            ])
            ->all();

        // 6. Auth User
        $authUser = null;
        if (auth()->check()) {
            $user = auth()->user();
            $authUser = [
                'id' => (string) $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ];
        }

        return Inertia::render('marketplace/checkout/TenantOrderConfirmationPage', [
            'domain' => $host,
            'store_settings' => $storeSettings,
            'categories' => $categories,
            'order' => $orderDetails,
            'auth_user' => $authUser,
        ]);
    }
}
