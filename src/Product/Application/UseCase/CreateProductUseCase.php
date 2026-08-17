<?php

declare(strict_types=1);

namespace Src\Product\Application\UseCase;

use Src\Product\Application\Contracts\ProductRepositoryInterface;
use Src\Product\Application\DTOs\ProductImageData;
use Src\Product\Application\DTOs\ProductVariantData;
use Src\Product\Domain\Entities\Product;
use Src\Product\Domain\Entities\ProductImage;
use Src\Product\Domain\Entities\ProductVariant;
use Src\Product\Domain\Exceptions\ProductSkuAlreadyExistsException;
use Src\Product\Domain\Exceptions\ProductSlugAlreadyExistsException;
use Src\Product\Domain\ValueObjects\ProductDescription;
use Src\Product\Domain\ValueObjects\ProductDimensions;
use Src\Product\Domain\ValueObjects\ProductName;
use Src\Product\Domain\ValueObjects\ProductPrice;
use Src\Product\Domain\ValueObjects\ProductSku;
use Src\Product\Domain\ValueObjects\ProductSlug;
use Src\Product\Domain\ValueObjects\ProductStatus;
use Src\Product\Domain\ValueObjects\ProductStock;

final class CreateProductUseCase
{
    public function __construct(
        private readonly ProductRepositoryInterface $repository
    ) {}

    /**
     * @param  ProductImageData[]  $images
     * @param  ProductVariantData[]  $variants
     */
    public function execute(
        string $name,
        string $slug,
        string $sku,
        float $price,
        int $quantity,
        ?float $comparePrice = null,
        ?float $costPrice = null,
        int $minQuantity = 1,
        int $maxQuantity = 100,
        bool $trackQuantity = true,
        bool $isVisible = true,
        bool $isFeatured = false,
        bool $isDigital = false,
        ?string $description = null,
        ?string $shortDescription = null,
        ?string $barcode = null,
        ?string $digitalProductUrl = null,
        ?float $weight = null,
        ?float $height = null,
        ?float $width = null,
        ?float $length = null,
        ?int $categoryId = null,
        ?int $brandId = null,
        ?string $publishedAt = null,
        ?array $seo = null,
        ?array $specifications = null,
        ?array $metadata = null,
        array $images = [],
        array $variants = []
    ): Product {
        $productSlug = ProductSlug::fromString($slug);
        $productSku = ProductSku::fromString($sku);

        if ($this->repository->findBySlug($productSlug) !== null) {
            throw new ProductSlugAlreadyExistsException($productSlug->value());
        }

        if ($this->repository->findBySku($productSku) !== null) {
            throw new ProductSkuAlreadyExistsException($productSku->value());
        }

        $domainImages = array_map(
            fn (ProductImageData $img) => ProductImage::create(
                imagePath: $img->imagePath,
                altText: $img->altText,
                isDefault: $img->isDefault,
                order: $img->order
            ),
            $images
        );

        $domainVariants = array_map(
            fn (ProductVariantData $v) => ProductVariant::create(
                sku: $v->sku,
                price: $v->price,
                comparePrice: $v->comparePrice,
                costPrice: $v->costPrice,
                quantity: $v->quantity,
                image: $v->image,
                weight: $v->weight,
                attributes: $v->attributes,
                attributeValueIds: $v->attributeValueIds
            ),
            $variants
        );

        $product = Product::create(
            name: ProductName::make($name),
            slug: $productSlug,
            sku: $productSku,
            price: ProductPrice::create($price, $comparePrice, $costPrice),
            stock: ProductStock::create($quantity, $minQuantity, $maxQuantity, $trackQuantity),
            dimensions: ProductDimensions::create($weight, $height, $width, $length),
            status: ProductStatus::create($isVisible, $isFeatured, $isDigital),
            description: ProductDescription::create($description, $shortDescription),
            barcode: $barcode,
            digitalProductUrl: $digitalProductUrl,
            categoryId: $categoryId,
            brandId: $brandId,
            publishedAt: $publishedAt,
            seo: $seo,
            specifications: $specifications,
            metadata: $metadata,
            images: $domainImages,
            variants: $domainVariants
        );

        return $this->repository->save($product);
    }
}
