<?php

declare(strict_types=1);

namespace Src\Product\Domain\Entities;

use Src\Product\Domain\ValueObjects\ProductDescription;
use Src\Product\Domain\ValueObjects\ProductDimensions;
use Src\Product\Domain\ValueObjects\ProductId;
use Src\Product\Domain\ValueObjects\ProductName;
use Src\Product\Domain\ValueObjects\ProductPrice;
use Src\Product\Domain\ValueObjects\ProductSku;
use Src\Product\Domain\ValueObjects\ProductSlug;
use Src\Product\Domain\ValueObjects\ProductStatus;
use Src\Product\Domain\ValueObjects\ProductStock;

final class Product
{
    /**
     * @param  ProductImage[]  $images
     * @param  ProductVariant[]  $variants
     */
    public function __construct(
        private ?ProductId $id,
        private ProductName $name,
        private ProductSlug $slug,
        private ProductSku $sku,
        private ProductPrice $price,
        private ProductStock $stock,
        private ProductDimensions $dimensions = new ProductDimensions,
        private ProductStatus $status = new ProductStatus,
        private ProductDescription $description = new ProductDescription,
        private ?string $barcode = null,
        private ?string $digitalProductUrl = null,
        private ?int $categoryId = null,
        private ?int $brandId = null,
        private ?string $publishedAt = null,
        private ?array $seo = null,
        private ?array $specifications = null,
        private ?array $metadata = null,
        private array $images = [],
        private array $variants = [],
        private ?string $categoryName = null,
        private ?string $brandName = null,
        private ?string $createdAt = null,
        private ?string $updatedAt = null
    ) {}

    public static function create(
        ProductName $name,
        ProductSlug $slug,
        ProductSku $sku,
        ProductPrice $price,
        ProductStock $stock,
        ?ProductDimensions $dimensions = null,
        ?ProductStatus $status = null,
        ?ProductDescription $description = null,
        ?string $barcode = null,
        ?string $digitalProductUrl = null,
        ?int $categoryId = null,
        ?int $brandId = null,
        ?string $publishedAt = null,
        ?array $seo = null,
        ?array $specifications = null,
        ?array $metadata = null,
        array $images = [],
        array $variants = []
    ): self {
        return new self(
            id: null,
            name: $name,
            slug: $slug,
            sku: $sku,
            price: $price,
            stock: $stock,
            dimensions: $dimensions ?? ProductDimensions::create(),
            status: $status ?? ProductStatus::create(),
            description: $description ?? ProductDescription::create(),
            barcode: $barcode ? trim($barcode) : null,
            digitalProductUrl: $digitalProductUrl ? trim($digitalProductUrl) : null,
            categoryId: $categoryId,
            brandId: $brandId,
            publishedAt: $publishedAt,
            seo: $seo,
            specifications: $specifications,
            metadata: $metadata,
            images: $images,
            variants: $variants
        );
    }

    public function id(): ?ProductId
    {
        return $this->id;
    }

    public function name(): ProductName
    {
        return $this->name;
    }

    public function slug(): ProductSlug
    {
        return $this->slug;
    }

    public function sku(): ProductSku
    {
        return $this->sku;
    }

    public function price(): ProductPrice
    {
        return $this->price;
    }

    public function stock(): ProductStock
    {
        return $this->stock;
    }

    public function dimensions(): ProductDimensions
    {
        return $this->dimensions;
    }

    public function status(): ProductStatus
    {
        return $this->status;
    }

    public function description(): ProductDescription
    {
        return $this->description;
    }

    public function barcode(): ?string
    {
        return $this->barcode;
    }

    public function digitalProductUrl(): ?string
    {
        return $this->digitalProductUrl;
    }

    public function categoryId(): ?int
    {
        return $this->categoryId;
    }

    public function brandId(): ?int
    {
        return $this->brandId;
    }

    public function publishedAt(): ?string
    {
        return $this->publishedAt;
    }

    public function seo(): ?array
    {
        return $this->seo;
    }

    public function specifications(): ?array
    {
        return $this->specifications;
    }

    public function metadata(): ?array
    {
        return $this->metadata;
    }

    /**
     * @return ProductImage[]
     */
    public function images(): array
    {
        return $this->images;
    }

    /**
     * @return ProductVariant[]
     */
    public function variants(): array
    {
        return $this->variants;
    }

    public function categoryName(): ?string
    {
        return $this->categoryName;
    }

    public function brandName(): ?string
    {
        return $this->brandName;
    }

    public function createdAt(): ?string
    {
        return $this->createdAt;
    }

    public function updatedAt(): ?string
    {
        return $this->updatedAt;
    }

    public function changePrice(ProductPrice $price): void
    {
        $this->price = $price;
    }

    public function updateStock(int $quantity): void
    {
        $this->stock = $this->stock->withQuantity($quantity);
    }

    public function incrementStock(int $amount): void
    {
        $this->updateStock($this->stock->quantity() + $amount);
    }

    public function decrementStock(int $amount): void
    {
        $newQty = max(0, $this->stock->quantity() - $amount);
        $this->updateStock($newQty);
    }

    public function toggleVisibility(): void
    {
        $this->status = $this->status->withVisibility(! $this->status->isVisible());
    }

    public function setVisibility(bool $isVisible): void
    {
        $this->status = $this->status->withVisibility($isVisible);
    }

    public function setFeatured(bool $isFeatured): void
    {
        $this->status = $this->status->withFeatured($isFeatured);
    }

    public function assignCategory(?int $categoryId): void
    {
        $this->categoryId = $categoryId;
    }

    public function assignBrand(?int $brandId): void
    {
        $this->brandId = $brandId;
    }

    public function setImages(array $images): void
    {
        $this->images = $images;
    }

    public function setVariants(array $variants): void
    {
        $this->variants = $variants;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id?->value(),
            'name' => $this->name->value(),
            'slug' => $this->slug->value(),
            'sku' => $this->sku->value(),
            'price' => $this->price->price(),
            'compare_price' => $this->price->comparePrice(),
            'cost_price' => $this->price->costPrice(),
            'quantity' => $this->stock->quantity(),
            'min_quantity' => $this->stock->minQuantity(),
            'max_quantity' => $this->stock->maxQuantity(),
            'track_quantity' => $this->stock->trackQuantity(),
            'is_in_stock' => $this->stock->isInStock(),
            'is_visible' => $this->status->isVisible(),
            'is_featured' => $this->status->isFeatured(),
            'is_digital' => $this->status->isDigital(),
            'digital_product_url' => $this->digitalProductUrl,
            'description' => $this->description->description(),
            'short_description' => $this->description->shortDescription(),
            'barcode' => $this->barcode,
            'weight' => $this->dimensions->weight(),
            'height' => $this->dimensions->height(),
            'width' => $this->dimensions->width(),
            'length' => $this->dimensions->length(),
            'category_id' => $this->categoryId,
            'category_name' => $this->categoryName,
            'brand_id' => $this->brandId,
            'brand_name' => $this->brandName,
            'published_at' => $this->publishedAt,
            'seo' => $this->seo,
            'specifications' => $this->specifications,
            'metadata' => $this->metadata,
            'images' => array_map(fn (ProductImage $img) => $img->toArray(), $this->images),
            'variants' => array_map(fn (ProductVariant $v) => $v->toArray(), $this->variants),
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }
}
