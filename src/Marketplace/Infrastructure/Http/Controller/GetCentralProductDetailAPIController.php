<?php

declare(strict_types=1);

namespace Src\Marketplace\Infrastructure\Http\Controller;

use App\Models\CentralProduct;
use Exception;
use Illuminate\Http\JsonResponse;
use Src\Shared\Helper\ApiResponse;

final class GetCentralProductDetailAPIController
{
    public function __invoke(string $slugOrId): JsonResponse
    {
        $product = CentralProduct::with('tenant.domains')
            ->where('is_visible', true)
            ->where(function ($q) use ($slugOrId) {
                $q->where('slug', $slugOrId)->orWhere('id', $slugOrId)->orWhere('tenant_product_id', $slugOrId);
            })
            ->first();

        if (! $product) {
            return ApiResponse::error(
                message: 'Producto no encontrado en el catálogo del marketplace',
                code: 404
            );
        }

        $tenant = $product->tenant;
        $domain = $tenant && $tenant->domains->isNotEmpty() ? $tenant->domains->first()->domain : null;

        $store = [
            'id' => $tenant?->id ?? $product->tenant_id,
            'name' => $tenant?->name ?? 'Tienda Asociada',
            'slug' => $tenant?->slug ?? $tenant?->id,
            'domain' => $domain,
            'description' => $tenant?->description,
            'logo' => $tenant?->logo,
            'banner' => $tenant?->banner,
        ];

        // Related products from same category or same store
        $related = CentralProduct::with('tenant.domains')
            ->where('is_visible', true)
            ->where('id', '!=', $product->id)
            ->where(function ($q) use ($product) {
                $q->where('category_name', $product->category_name)
                    ->orWhere('tenant_id', $product->tenant_id);
            })
            ->take(4)
            ->get()
            ->map(function ($p) {
                $t = $p->tenant;
                return [
                    'id' => $p->id,
                    'tenant_id' => $p->tenant_id,
                    'tenant_product_id' => $p->tenant_product_id,
                    'tenant_name' => $t ? ($t->name ?? 'Tienda') : 'Tienda Asociada',
                    'tenant_domain' => $t && $t->domains->isNotEmpty() ? $t->domains->first()->domain : null,
                    'name' => $p->name,
                    'slug' => $p->slug,
                    'price' => (float) $p->price,
                    'quantity' => (int) $p->quantity,
                    'images' => $p->images ?? [],
                    'category_name' => $p->category_name,
                ];
            });

        return ApiResponse::success(
            data: [
                'product' => [
                    'id' => $product->id,
                    'tenant_id' => $product->tenant_id,
                    'tenant_product_id' => $product->tenant_product_id,
                    'tenant_name' => $tenant?->name ?? 'Tienda Asociada',
                    'tenant_domain' => $domain,
                    'name' => $product->name,
                    'slug' => $product->slug,
                    'description' => $product->description,
                    'sku' => $product->sku,
                    'barcode' => $product->barcode,
                    'price' => (float) $product->price,
                    'compare_price' => $product->compare_price !== null ? (float) $product->compare_price : null,
                    'quantity' => (int) $product->quantity,
                    'is_visible' => (bool) $product->is_visible,
                    'is_featured' => (bool) $product->is_featured,
                    'category_name' => $product->category_name,
                    'brand_name' => $product->brand_name,
                    'images' => $product->images ?? [],
                    'variants' => $product->variants ?? [],
                    'specifications' => $product->specifications,
                    'metadata' => $product->metadata,
                    'created_at' => $product->created_at?->toIso8601String(),
                ],
                'store' => $store,
                'related' => $related,
            ],
            message: 'Detalle del producto recuperado exitosamente'
        );
    }
}
