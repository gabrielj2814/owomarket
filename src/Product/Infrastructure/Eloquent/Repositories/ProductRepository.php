<?php

declare(strict_types=1);

namespace Src\Product\Infrastructure\Eloquent\Repositories;

use Illuminate\Support\Facades\DB;
use Src\Product\Application\Contracts\ProductMediaStorageInterface;
use Src\Product\Application\Contracts\ProductRepositoryInterface;
use Src\Product\Application\DTOs\PaginatedProductsResult;
use Src\Product\Application\DTOs\ProductFilterCriteria;
use Src\Product\Domain\Entities\Product;
use Src\Product\Domain\Entities\ProductImage;
use Src\Product\Domain\Entities\ProductVariant;
use Src\Product\Domain\ValueObjects\ProductDescription;
use Src\Product\Domain\ValueObjects\ProductDimensions;
use Src\Product\Domain\ValueObjects\ProductId;
use Src\Product\Domain\ValueObjects\ProductName;
use Src\Product\Domain\ValueObjects\ProductPrice;
use Src\Product\Domain\ValueObjects\ProductSku;
use Src\Product\Domain\ValueObjects\ProductSlug;
use Src\Product\Domain\ValueObjects\ProductStatus;
use Src\Product\Domain\ValueObjects\ProductStock;
use Src\Product\Infrastructure\Eloquent\Models\Product as EloquentProduct;
use Src\Product\Infrastructure\Eloquent\Models\ProductImage as EloquentProductImage;
use Src\Product\Infrastructure\Eloquent\Models\ProductVariant as EloquentProductVariant;

final class ProductRepository implements ProductRepositoryInterface
{
    public function __construct(
        private readonly ProductMediaStorageInterface $mediaStorage
    ) {}

    public function save(Product $product): Product
    {
        return DB::transaction(function () use ($product) {
            $model = EloquentProduct::create([
                'name' => $product->name()->value(),
                'slug' => $product->slug()->value(),
                'sku' => $product->sku()->value(),
                'price' => $product->price()->price(),
                'compare_price' => $product->price()->comparePrice(),
                'cost_price' => $product->price()->costPrice(),
                'quantity' => $product->stock()->quantity(),
                'min_quantity' => $product->stock()->minQuantity(),
                'max_quantity' => $product->stock()->maxQuantity(),
                'track_quantity' => $product->stock()->trackQuantity(),
                'is_visible' => $product->status()->isVisible(),
                'is_featured' => $product->status()->isFeatured(),
                'is_digital' => $product->status()->isDigital(),
                'digital_product_url' => $product->digitalProductUrl(),
                'description' => $product->description()->description(),
                'short_description' => $product->description()->shortDescription(),
                'barcode' => $product->barcode(),
                'weight' => $product->dimensions()->weight(),
                'height' => $product->dimensions()->height(),
                'width' => $product->dimensions()->width(),
                'length' => $product->dimensions()->length(),
                'category_id' => $product->categoryId(),
                'brand_id' => $product->brandId(),
                'published_at' => $product->publishedAt(),
                'seo' => $product->seo(),
                'specifications' => $product->specifications(),
                'metadata' => $product->metadata(),
            ]);

            foreach ($product->images() as $image) {
                EloquentProductImage::create([
                    'product_id' => $model->id,
                    'image_path' => $image->imagePath(),
                    'alt_text' => $image->altText(),
                    'is_default' => $image->isDefault(),
                    'order' => $image->order(),
                ]);
            }

            foreach ($product->variants() as $variant) {
                $variantModel = EloquentProductVariant::create([
                    'product_id' => $model->id,
                    'sku' => $variant->sku(),
                    'price' => $variant->price(),
                    'compare_price' => $variant->comparePrice(),
                    'cost_price' => $variant->costPrice(),
                    'quantity' => $variant->quantity(),
                    'image' => $variant->image(),
                    'weight' => $variant->weight(),
                    'attributes' => $variant->attributes(),
                ]);

                if (! empty($variant->attributeValueIds())) {
                    $variantModel->attributeValues()->sync($variant->attributeValueIds());
                }
            }

            return $this->toDomain($model->fresh(['category', 'brand', 'images', 'variants.attributeValues']));
        });
    }

    public function findById(ProductId $id): ?Product
    {
        $model = EloquentProduct::with(['category', 'brand', 'images', 'variants.attributeValues'])->find($id->value());

        return $model ? $this->toDomain($model) : null;
    }

    public function findBySlug(ProductSlug $slug): ?Product
    {
        $model = EloquentProduct::with(['category', 'brand', 'images', 'variants.attributeValues'])->where('slug', $slug->value())->first();

        return $model ? $this->toDomain($model) : null;
    }

    public function findBySku(ProductSku $sku): ?Product
    {
        $model = EloquentProduct::with(['category', 'brand', 'images', 'variants.attributeValues'])->where('sku', $sku->value())->first();

        return $model ? $this->toDomain($model) : null;
    }

    public function update(Product $product): Product
    {
        return DB::transaction(function () use ($product) {
            $model = EloquentProduct::findOrFail($product->id()->value());

            $model->update([
                'name' => $product->name()->value(),
                'slug' => $product->slug()->value(),
                'sku' => $product->sku()->value(),
                'price' => $product->price()->price(),
                'compare_price' => $product->price()->comparePrice(),
                'cost_price' => $product->price()->costPrice(),
                'quantity' => $product->stock()->quantity(),
                'min_quantity' => $product->stock()->minQuantity(),
                'max_quantity' => $product->stock()->maxQuantity(),
                'track_quantity' => $product->stock()->trackQuantity(),
                'is_visible' => $product->status()->isVisible(),
                'is_featured' => $product->status()->isFeatured(),
                'is_digital' => $product->status()->isDigital(),
                'digital_product_url' => $product->digitalProductUrl(),
                'description' => $product->description()->description(),
                'short_description' => $product->description()->shortDescription(),
                'barcode' => $product->barcode(),
                'weight' => $product->dimensions()->weight(),
                'height' => $product->dimensions()->height(),
                'width' => $product->dimensions()->width(),
                'length' => $product->dimensions()->length(),
                'category_id' => $product->categoryId(),
                'brand_id' => $product->brandId(),
                'published_at' => $product->publishedAt(),
                'seo' => $product->seo(),
                'specifications' => $product->specifications(),
                'metadata' => $product->metadata(),
            ]);

            if (! empty($product->images())) {
                $this->upsertImages($model->id, $product->images());
            }

            if (! empty($product->variants())) {
                $this->upsertVariants($model->id, $product->variants());
            }

            return $this->toDomain($model->fresh(['category', 'brand', 'images', 'variants.attributeValues']));
        });
    }

    public function delete(ProductId $id): void
    {
        // Hallazgo E1: borraba con el query builder, que **no dispara eventos de modelo**,
        // así que la fila de `central_products` se quedaba con `is_visible = true` para
        // siempre y el producto seguía siendo comprable en el marketplace central.
        EloquentProduct::where('id', $id->value())->first()?->delete();
    }

    public function filter(ProductFilterCriteria $criteria): PaginatedProductsResult
    {
        $query = EloquentProduct::with(['category', 'brand', 'images', 'variants.attributeValues']);

        if ($criteria->search !== null && trim($criteria->search) !== '') {
            $search = '%'.trim($criteria->search).'%';
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', $search)
                    ->orWhere('sku', 'like', $search)
                    ->orWhere('slug', 'like', $search)
                    ->orWhere('description', 'like', $search);
            });
        }

        if ($criteria->categoryId !== null) {
            $query->where('category_id', $criteria->categoryId);
        }

        if ($criteria->brandId !== null) {
            $query->where('brand_id', $criteria->brandId);
        }

        if ($criteria->minPrice !== null) {
            $query->where('price', '>=', $criteria->minPrice);
        }

        if ($criteria->maxPrice !== null) {
            $query->where('price', '<=', $criteria->maxPrice);
        }

        if ($criteria->isVisible !== null) {
            $query->where('is_visible', $criteria->isVisible);
        }

        if ($criteria->isFeatured !== null) {
            $query->where('is_featured', $criteria->isFeatured);
        }

        if ($criteria->isDigital !== null) {
            $query->where('is_digital', $criteria->isDigital);
        }

        if ($criteria->inStock !== null) {
            if ($criteria->inStock) {
                $query->where(function ($q) {
                    $q->where('track_quantity', false)->orWhere('quantity', '>', 0);
                });
            } else {
                $query->where('track_quantity', true)->where('quantity', '<=', 0);
            }
        }

        $allowedSorts = ['id', 'name', 'price', 'quantity', 'created_at', 'published_at'];
        $sortBy = in_array($criteria->sortBy, $allowedSorts, true) ? $criteria->sortBy : 'created_at';
        $sortDirection = strtolower($criteria->sortDirection) === 'asc' ? 'asc' : 'desc';

        $query->orderBy($sortBy, $sortDirection);

        $paginator = $query->paginate(
            perPage: $criteria->perPage,
            page: $criteria->page
        );

        $items = array_map(
            fn (EloquentProduct $model) => $this->toDomain($model),
            $paginator->items()
        );

        return new PaginatedProductsResult(
            items: $items,
            total: $paginator->total(),
            currentPage: $paginator->currentPage(),
            perPage: $paginator->perPage(),
            lastPage: $paginator->lastPage()
        );
    }

    public function toggleVisibility(ProductId $id, bool $isVisible): void
    {
        // Hallazgo E1: mismo motivo que `delete()`. Ocultar un producto en la tienda no
        // lo retiraba del marketplace porque este `update()` no pasaba por el observador.
        EloquentProduct::where('id', $id->value())->first()?->update(['is_visible' => $isVisible]);
    }

    public function updateStock(ProductId $id, int $quantity): void
    {
        // Hallazgo E2: este método era el ÚNICO que propagaba algo a `central_products`, y
        // lo hacía con una copia a mano del stock envuelta en un `catch` vacío. Ahora
        // guarda sobre el modelo y es `ProductObserver` quien sincroniza la fila entera
        // —precio, nombre e imágenes incluidos—, registrando el fallo si lo hay.
        EloquentProduct::where('id', $id->value())->first()?->update(['quantity' => max(0, $quantity)]);
    }

    /**
     * Hallazgo E4: `update()` borraba físicamente **todas** las filas de
     * `product_images` y `product_variants` y las recreaba con UUID nuevos en cada
     * edición. Corregir una errata en la descripción bastaba para que todas las variantes
     * cambiaran de id: los `order_items.product_variant_id` de pedidos históricos quedaban
     * huérfanos, los carritos de los clientes apuntaban a variantes inexistentes y el
     * array `variants` ya sincronizado en `central_products` quedaba inconsistente.
     *
     * Ahora se actualiza lo que ya existía, se crea sólo lo nuevo y se borra únicamente lo
     * que el comerciante quitó de verdad.
     *
     * @param  array<int, ProductVariant>  $variants
     */
    private function upsertVariants(string $productId, array $variants): void
    {
        $keptIds = [];

        foreach ($variants as $variant) {
            $attributes = [
                'product_id' => $productId,
                'sku' => $variant->sku(),
                'price' => $variant->price(),
                'compare_price' => $variant->comparePrice(),
                'cost_price' => $variant->costPrice(),
                'quantity' => $variant->quantity(),
                'image' => $variant->image(),
                'weight' => $variant->weight(),
                'attributes' => $variant->attributes(),
            ];

            $existing = $variant->id() !== null
                ? EloquentProductVariant::where('product_id', $productId)->find($variant->id())
                : null;

            if ($existing) {
                $existing->update($attributes);
                $variantModel = $existing;
            } else {
                $variantModel = EloquentProductVariant::create($attributes);
            }

            $keptIds[] = $variantModel->id;

            if (! empty($variant->attributeValueIds())) {
                $variantModel->attributeValues()->sync($variant->attributeValueIds());
            }
        }

        EloquentProductVariant::where('product_id', $productId)
            ->whereNotIn('id', $keptIds)
            ->delete();
    }

    /**
     * Mismo criterio que `upsertVariants()` (hallazgo E4). Además, al eliminar una imagen
     * se borra también el fichero físico: el borrado anterior dejaba huérfano en disco
     * cada fichero de cada edición.
     *
     * @param  array<int, ProductImage>  $images
     */
    private function upsertImages(string $productId, array $images): void
    {
        $keptIds = [];

        foreach ($images as $image) {
            $attributes = [
                'product_id' => $productId,
                'image_path' => $image->imagePath(),
                'alt_text' => $image->altText(),
                'is_default' => $image->isDefault(),
                'order' => $image->order(),
            ];

            $existing = $image->id() !== null
                ? EloquentProductImage::where('product_id', $productId)->find($image->id())
                : null;

            if ($existing) {
                $existing->update($attributes);
                $keptIds[] = $existing->id;

                continue;
            }

            $keptIds[] = EloquentProductImage::create($attributes)->id;
        }

        $removed = EloquentProductImage::where('product_id', $productId)
            ->whereNotIn('id', $keptIds)
            ->get();

        foreach ($removed as $image) {
            $this->mediaStorage->deleteImage($image->image_path);
            $image->delete();
        }
    }

    private function toDomain(EloquentProduct $model): Product
    {
        $images = [];
        if ($model->relationLoaded('images') && $model->images) {
            $images = $model->images->map(fn (EloquentProductImage $img) => new ProductImage(
                id: (string) $img->id,
                imagePath: $img->image_path,
                altText: $img->alt_text,
                isDefault: (bool) $img->is_default,
                order: (int) ($img->order ?? 0)
            ))->all();
        }

        $variants = [];
        if ($model->relationLoaded('variants') && $model->variants) {
            $variants = $model->variants->map(function (EloquentProductVariant $v) {
                $attrValIds = [];
                if ($v->relationLoaded('attributeValues') && $v->attributeValues) {
                    $attrValIds = $v->attributeValues->pluck('id')->map(fn ($id) => (string) $id)->all();
                }

                return new ProductVariant(
                    id: (string) $v->id,
                    sku: $v->sku,
                    price: (float) $v->price,
                    comparePrice: $v->compare_price !== null ? (float) $v->compare_price : null,
                    costPrice: $v->cost_price !== null ? (float) $v->cost_price : null,
                    quantity: (int) ($v->quantity ?? 0),
                    image: $v->image,
                    weight: $v->weight !== null ? (float) $v->weight : null,
                    attributes: is_array($v->attributes) ? $v->attributes : [],
                    attributeValueIds: $attrValIds
                );
            })->all();
        }

        $publishedAt = $model->published_at instanceof \DateTimeInterface
            ? $model->published_at->format('Y-m-d')
            : ($model->published_at ? (string) substr((string) $model->published_at, 0, 10) : null);

        return new Product(
            id: ProductId::fromString((string) $model->id),
            name: ProductName::make($model->name),
            slug: ProductSlug::fromString($model->slug),
            sku: ProductSku::fromString($model->sku),
            price: ProductPrice::create(
                price: (float) $model->price,
                comparePrice: $model->compare_price !== null ? (float) $model->compare_price : null,
                costPrice: $model->cost_price !== null ? (float) $model->cost_price : null
            ),
            stock: ProductStock::create(
                quantity: (int) ($model->quantity ?? 0),
                minQuantity: (int) ($model->min_quantity ?? 1),
                maxQuantity: (int) ($model->max_quantity ?? 100),
                trackQuantity: (bool) $model->track_quantity
            ),
            dimensions: ProductDimensions::create(
                weight: $model->weight !== null ? (float) $model->weight : null,
                height: $model->height !== null ? (float) $model->height : null,
                width: $model->width !== null ? (float) $model->width : null,
                length: $model->length !== null ? (float) $model->length : null
            ),
            status: ProductStatus::create(
                isVisible: (bool) $model->is_visible,
                isFeatured: (bool) $model->is_featured,
                isDigital: (bool) $model->is_digital
            ),
            description: ProductDescription::create(
                description: $model->description,
                shortDescription: $model->short_description
            ),
            barcode: $model->barcode,
            digitalProductUrl: $model->digital_product_url,
            categoryId: $model->category_id !== null ? (int) $model->category_id : null,
            brandId: $model->brand_id !== null ? (int) $model->brand_id : null,
            publishedAt: $publishedAt,
            seo: $model->seo,
            specifications: $model->specifications,
            metadata: $model->metadata,
            images: $images,
            variants: $variants,
            categoryName: $model->category?->name,
            brandName: $model->brand?->name,
            createdAt: $model->created_at?->toISOString(),
            updatedAt: $model->updated_at?->toISOString()
        );
    }
}
