<?php

declare(strict_types=1);

namespace Src\Product\Application\UseCase;

use Illuminate\Support\Str;
use Src\Product\Infrastructure\Eloquent\Models\CentralProduct;
use Src\Product\Infrastructure\Eloquent\Models\Product as EloquentProduct;

/**
 * Proyecta un producto de una tienda sobre el catálogo del marketplace central.
 *
 * Hallazgos E1 y E2: este caso de uso existía pero **sólo lo invocaba
 * `ToggleProductMarketplacePublicationUseCase`**, es decir, únicamente cuando el
 * comerciante pulsaba «publicar en el marketplace». Ni al crear, ni al editar, ni al
 * borrar, ni al ocultar, ni al vender se volvía a llamar, así que el catálogo central se
 * quedaba congelado en el estado que tenía el día de la publicación: precios viejos,
 * productos borrados que seguían siendo comprables y stock que nunca bajaba.
 *
 * Ahora lo dispara `ProductObserver` desde los eventos del modelo, que es el único sitio
 * por el que pasan todos los caminos.
 */
final class SyncProductToCentralMarketplaceUseCase
{
    /**
     * Claves de `metadata` que son del catálogo central y no del producto de la tienda.
     * Si no se preservan, cada sincronización borraría el historial de moderación y la
     * comisión personalizada que fijó el superadmin.
     */
    private const CENTRAL_ONLY_METADATA_KEYS = ['moderation_history', 'custom_commission_rate'];

    public function execute(EloquentProduct $product): ?CentralProduct
    {
        $tenantId = tenant('id');

        if (! $tenantId) {
            return null;
        }

        $product->loadMissing(['category', 'brand', 'images', 'variants.attributeValues']);

        $existing = CentralProduct::where('tenant_id', $tenantId)
            ->where('tenant_product_id', (string) $product->id)
            ->first();

        if (! $product->is_published_central) {
            // No publicado en el marketplace: si había fila central, se oculta.
            if ($existing) {
                $existing->is_visible = false;
                $existing->quantity = (int) $product->quantity;
                $existing->save();
            }

            return $existing;
        }

        $imagesData = $product->images->map(fn ($img) => [
            'id' => (string) $img->id,
            'image_path' => $img->image_path,
            'alt_text' => $img->alt_text,
            'is_default' => (bool) $img->is_default,
            'order' => (int) ($img->order ?? 0),
        ])->toArray();

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

        return CentralProduct::updateOrCreate(
            [
                'tenant_id' => $tenantId,
                'tenant_product_id' => (string) $product->id,
            ],
            [
                'id' => $existing?->id ?? (string) Str::uuid(),
                'name' => $product->name,
                'slug' => $product->slug,
                'description' => $product->description,
                'sku' => $product->sku,
                'barcode' => $product->barcode,
                'price' => (float) $product->price,
                'compare_price' => $product->compare_price !== null ? (float) $product->compare_price : null,
                'cost_price' => $product->cost_price !== null ? (float) $product->cost_price : null,
                'quantity' => (int) $product->quantity,
                // Hallazgo E1: antes se forzaba `true`, así que ocultar un producto en la
                // tienda no lo retiraba del marketplace. Ahora manda la tienda, salvo que
                // el moderador lo haya vetado — esa decisión no la revierte el comerciante.
                'is_visible' => (bool) $product->is_visible && ! (bool) ($existing?->is_blocked_by_admin ?? false),
                'is_featured' => (bool) $product->is_featured,
                'category_name' => $product->category?->name,
                'brand_name' => $product->brand?->name,
                'images' => $imagesData,
                'variants' => $variantsData,
                'specifications' => $product->specifications,
                'metadata' => $this->mergeMetadata($existing, $product->metadata),
            ]
        );
    }

    /**
     * Retira el producto del marketplace central sin borrar la fila.
     *
     * Hallazgo E1: `ProductRepository::delete()` sólo hacía un soft delete en la tienda,
     * así que la fila central se quedaba con `is_visible = true` para siempre y el
     * producto seguía apareciendo y siendo comprable en el marketplace.
     *
     * Se conserva la fila en lugar de borrarla porque `Product` usa `SoftDeletes`: si el
     * comerciante restaura el producto, vuelve con su mismo id central, su historial de
     * moderación y su comisión personalizada.
     */
    public function withdraw(EloquentProduct $product): ?CentralProduct
    {
        $tenantId = tenant('id');

        if (! $tenantId) {
            return null;
        }

        $centralProduct = CentralProduct::where('tenant_id', $tenantId)
            ->where('tenant_product_id', (string) $product->id)
            ->first();

        if (! $centralProduct) {
            return null;
        }

        $centralProduct->is_visible = false;
        $centralProduct->save();

        return $centralProduct;
    }

    /**
     * Conserva las claves que son del catálogo central sobre las que trae la tienda.
     */
    private function mergeMetadata(?CentralProduct $existing, mixed $tenantMetadata): array
    {
        $metadata = is_array($tenantMetadata) ? $tenantMetadata : [];
        $centralMetadata = is_array($existing?->metadata) ? $existing->metadata : [];

        foreach (self::CENTRAL_ONLY_METADATA_KEYS as $key) {
            if (array_key_exists($key, $centralMetadata)) {
                $metadata[$key] = $centralMetadata[$key];
            }
        }

        return $metadata;
    }
}
