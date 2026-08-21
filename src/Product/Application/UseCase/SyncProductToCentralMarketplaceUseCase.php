<?php

declare(strict_types=1);

namespace Src\Product\Application\UseCase;

use Src\Product\Infrastructure\Eloquent\Models\CentralProduct;
use Illuminate\Support\Str;
use Src\Product\Infrastructure\Eloquent\Models\Product as EloquentProduct;

final class SyncProductToCentralMarketplaceUseCase
{
    /**
     * @param EloquentProduct $product
     * @return CentralProduct|null
     */
    public function execute(EloquentProduct $product): ?CentralProduct
    {
        $tenantId = tenant('id');
        if (! $tenantId) {
            return null;
        }

        $product->loadMissing(['category', 'brand', 'images', 'variants.attributeValues']);

        if (! $product->is_published_central) {
            // If product is not published to central marketplace, hide it in central_products
            $centralProduct = CentralProduct::where('tenant_id', $tenantId)
                ->where('tenant_product_id', (string) $product->id)
                ->first();

            if ($centralProduct) {
                $centralProduct->is_visible = false;
                $centralProduct->quantity = (int) $product->quantity;
                $centralProduct->save();
            }

            return $centralProduct;
        }

        // Map images array
        $imagesData = $product->images->map(fn ($img) => [
            'id' => (string) $img->id,
            'image_path' => $img->image_path,
            'alt_text' => $img->alt_text,
            'is_default' => (bool) $img->is_default,
            'order' => (int) ($img->order ?? 0),
        ])->toArray();

        // Map variants array
        $variantsData = $product->variants->map(fn ($v) => [
            'id' => (string) $v->id,
            'sku' => $v->sku,
            'price' => (float) $v->price,
            'compare_price' => $v->compare_price !== null ? (float) $v->compare_price : null,
            'cost_price' => $v->cost_price !== null ? (float) $v->cost_price : null,
            'quantity' => (int) ($v->quantity ?? 0),
            'image' => $v->image,
            'weight' => $v->weight !== null ? (float) $v->weight : null,
            'attributes' => is_array($v->attributes) ? $v->attributes : [],
        ])->toArray();

        $centralProduct = CentralProduct::updateOrCreate(
            [
                'tenant_id' => $tenantId,
                'tenant_product_id' => (string) $product->id,
            ],
            [
                'id' => CentralProduct::where('tenant_id', $tenantId)->where('tenant_product_id', (string) $product->id)->value('id') ?? (string) Str::uuid(),
                'name' => $product->name,
                'slug' => $product->slug,
                'description' => $product->description,
                'sku' => $product->sku,
                'barcode' => $product->barcode,
                'price' => (float) $product->price,
                'compare_price' => $product->compare_price !== null ? (float) $product->compare_price : null,
                'cost_price' => $product->cost_price !== null ? (float) $product->cost_price : null,
                'quantity' => (int) $product->quantity,
                'is_visible' => true,
                'is_featured' => (bool) $product->is_featured,
                'category_name' => $product->category?->name,
                'brand_name' => $product->brand?->name,
                'images' => $imagesData,
                'variants' => $variantsData,
                'specifications' => $product->specifications,
                'metadata' => $product->metadata,
            ]
        );

        return $centralProduct;
    }
}
