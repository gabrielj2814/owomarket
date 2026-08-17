<?php

declare(strict_types=1);

use InvalidArgumentException;
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

describe('Product Value Objects & Domain Entities', function () {
    test('ProductId creates valid uuid and validates equality', function () {
        $uuid = '018f3a9e-8c7a-7b3b-9a4a-1a2b3c4d5e6f';
        $productId = ProductId::fromString($uuid);
        $productId2 = ProductId::fromString($uuid);

        expect($productId->value())->toBe($uuid)
            ->and($productId->isNull())->toBeFalse()
            ->and($productId->equals($productId2))->toBeTrue();
    });

    test('ProductName validates minimum and maximum length', function () {
        $name = ProductName::make('Smartphone Ultra');
        expect($name->value())->toBe('Smartphone Ultra');

        expect(fn () => ProductName::make('A'))
            ->toThrow(InvalidArgumentException::class);
    });

    test('ProductSlug formats slug correctly and rejects empty', function () {
        $slug = ProductSlug::fromString('Smartphone Ultra 5G');
        expect($slug->value())->toBe('smartphone-ultra-5g');

        expect(fn () => ProductSlug::fromString('   '))
            ->toThrow(InvalidArgumentException::class);
    });

    test('ProductSku validates format and uppercase normalization', function () {
        $sku = ProductSku::fromString('prod-123_abc');
        expect($sku->value())->toBe('PROD-123_ABC');

        expect(fn () => ProductSku::fromString('A'))
            ->toThrow(InvalidArgumentException::class);

        expect(fn () => ProductSku::fromString('INVALID SKU!@#'))
            ->toThrow(InvalidArgumentException::class);
    });

    test('ProductPrice calculates discounts and prevents negative values', function () {
        $price = ProductPrice::create(100.00, 150.00, 70.00);

        expect($price->price())->toBe(100.00)
            ->and($price->comparePrice())->toBe(150.00)
            ->and($price->costPrice())->toBe(70.00)
            ->and($price->hasDiscount())->toBeTrue()
            ->and($price->discountPercentage())->toBe(33.33);

        expect(fn () => ProductPrice::create(-10.0))
            ->toThrow(InvalidArgumentException::class);
    });

    test('ProductStock handles stock tracking and boundaries', function () {
        $stock = ProductStock::create(25, 5, 50, true);

        expect($stock->quantity())->toBe(25)
            ->and($stock->minQuantity())->toBe(5)
            ->and($stock->maxQuantity())->toBe(50)
            ->and($stock->trackQuantity())->toBeTrue()
            ->and($stock->isInStock())->toBeTrue();

        $updatedStock = $stock->withQuantity(0);
        expect($updatedStock->quantity())->toBe(0)
            ->and($updatedStock->isInStock())->toBeFalse();

        expect(fn () => ProductStock::create(-1))
            ->toThrow(InvalidArgumentException::class);

        expect(fn () => ProductStock::create(10, 20, 10))
            ->toThrow(InvalidArgumentException::class);
    });

    test('ProductDimensions enforces non-negative measurements', function () {
        $dim = ProductDimensions::create(1.5, 10.0, 5.0, 20.0);

        expect($dim->weight())->toBe(1.5)
            ->and($dim->height())->toBe(10.0)
            ->and($dim->width())->toBe(5.0)
            ->and($dim->length())->toBe(20.0);

        expect(fn () => ProductDimensions::create(-1.0))
            ->toThrow(InvalidArgumentException::class);
    });

    test('Product entity business logic works without database', function () {
        $product = Product::create(
            name: ProductName::make('Laptop Pro'),
            slug: ProductSlug::fromString('laptop-pro'),
            sku: ProductSku::fromString('LAP-PRO-001'),
            price: ProductPrice::create(1200.00),
            stock: ProductStock::create(10, 1, 100),
            dimensions: ProductDimensions::create(2.1),
            status: ProductStatus::create(isVisible: true, isFeatured: false),
            description: ProductDescription::create('High performance laptop', 'Pro Laptop'),
            categoryId: 1,
            brandId: 2
        );

        expect($product->name()->value())->toBe('Laptop Pro')
            ->and($product->sku()->value())->toBe('LAP-PRO-001')
            ->and($product->stock()->quantity())->toBe(10)
            ->and($product->status()->isVisible())->toBeTrue();

        $product->updateStock(15);
        expect($product->stock()->quantity())->toBe(15);

        $product->incrementStock(5);
        expect($product->stock()->quantity())->toBe(20);

        $product->decrementStock(8);
        expect($product->stock()->quantity())->toBe(12);

        $product->toggleVisibility();
        expect($product->status()->isVisible())->toBeFalse();

        $product->setFeatured(true);
        expect($product->status()->isFeatured())->toBeTrue();

        $product->changePrice(ProductPrice::create(1100.00, 1300.00));
        expect($product->price()->price())->toBe(1100.00)
            ->and($product->price()->comparePrice())->toBe(1300.00);

        $variant = ProductVariant::create(
            sku: 'LAP-PRO-001-16GB',
            price: 1300.00,
            quantity: 5,
            attributes: ['RAM' => '16GB']
        );
        $product->setVariants([$variant]);
        expect($product->variants())->toHaveCount(1)
            ->and($product->variants()[0]->sku())->toBe('LAP-PRO-001-16GB');

        $image = ProductImage::create(
            imagePath: 'https://example.com/laptop.jpg',
            altText: 'Front view',
            isDefault: true,
            order: 1
        );
        $product->setImages([$image]);
        expect($product->images())->toHaveCount(1)
            ->and($product->images()[0]->imagePath())->toBe('https://example.com/laptop.jpg');

        $array = $product->toArray();
        expect($array['name'])->toBe('Laptop Pro')
            ->and($array['sku'])->toBe('LAP-PRO-001')
            ->and($array['price'])->toBe(1100.00)
            ->and($array['quantity'])->toBe(12)
            ->and($array['is_visible'])->toBeFalse()
            ->and($array['is_featured'])->toBeTrue()
            ->and($array['variants'])->toHaveCount(1)
            ->and($array['images'])->toHaveCount(1);
    });
});
