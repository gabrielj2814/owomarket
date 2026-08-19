<?php

declare(strict_types=1);

namespace Src\CentralCustomer\Application\UseCases;

use App\Models\CentralCustomerWishlist;
use Illuminate\Support\Str;

final class ToggleCustomerWishlistProductUseCase
{
    /**
     * @param  array{product_id: string, tenant_id: string, product_name: string, product_slug?: string|null, product_price: float, product_image?: string|null}  $data
     * @return array{in_wishlist: bool, message: string}
     */
    public function execute(string $customerId, array $data): array
    {
        $existing = CentralCustomerWishlist::where('customer_id', $customerId)
            ->where('product_id', $data['product_id'])
            ->where('tenant_id', $data['tenant_id'])
            ->first();

        if ($existing) {
            $existing->delete();

            return [
                'in_wishlist' => false,
                'message' => 'Producto eliminado de tus favoritos.',
            ];
        }

        CentralCustomerWishlist::create([
            'id' => (string) Str::uuid(),
            'customer_id' => $customerId,
            'product_id' => (string) $data['product_id'],
            'tenant_id' => (string) $data['tenant_id'],
            'product_name' => trim($data['product_name']),
            'product_slug' => isset($data['product_slug']) ? trim($data['product_slug']) : null,
            'product_price' => (float) $data['product_price'],
            'product_image' => $data['product_image'] ?? null,
        ]);

        return [
            'in_wishlist' => true,
            'message' => 'Producto agregado a tus favoritos.',
        ];
    }
}
