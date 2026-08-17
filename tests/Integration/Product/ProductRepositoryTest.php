<?php

declare(strict_types=1);

use Src\Brand\Domain\Entities\Brand;
use Src\Brand\Domain\ValueObjects\BrandName;
use Src\Brand\Domain\ValueObjects\BrandSlug;
use Src\Brand\Infrastructure\Eloquent\Repositories\BrandRepository;
use Src\Category\Domain\Entities\Category;
use Src\Category\Domain\ValueObjects\CategoryName;
use Src\Category\Domain\ValueObjects\CategorySlug;
use Src\Category\Infrastructure\Eloquent\Repositories\CategoryRepository;
use Src\Product\Application\DTOs\ProductFilterCriteria;
use Src\Product\Domain\Entities\Product;
use Src\Product\Domain\Entities\ProductImage;
use Src\Product\Domain\Entities\ProductVariant;
use Src\Product\Domain\ValueObjects\ProductDescription;
use Src\Product\Domain\ValueObjects\ProductDimensions;
use Src\Product\Domain\ValueObjects\ProductName;
use Src\Product\Domain\ValueObjects\ProductPrice;
use Src\Product\Domain\ValueObjects\ProductSku;
use Src\Product\Domain\ValueObjects\ProductSlug;
use Src\Product\Domain\ValueObjects\ProductStatus;
use Src\Product\Domain\ValueObjects\ProductStock;
use Src\Product\Infrastructure\Eloquent\Repositories\ProductRepository;

beforeEach(function () {
    (require base_path('database/migrations/tenant/2025_10_28_142911_create_categories.php'))->up();
    (require base_path('database/migrations/tenant/2025_10_28_143000_create_brands.php'))->up();
    (require base_path('database/migrations/tenant/2025_10_28_143038_create_products.php'))->up();
    (require base_path('database/migrations/tenant/2025_10_28_143251_create_product_images.php'))->up();
    (require base_path('database/migrations/tenant/2025_10_28_143325_create_product_attributes.php'))->up();
    (require base_path('database/migrations/tenant/2025_10_28_143921_create_product_attribute_values.php'))->up();
    (require base_path('database/migrations/tenant/2025_10_28_143954_create_product_variants.php'))->up();
    (require base_path('database/migrations/tenant/2025_10_28_144041_create_product_variant_attribute_values.php'))->up();

    $this->repository = new ProductRepository;
    $this->categoryRepository = new CategoryRepository;
    $this->brandRepository = new BrandRepository;
});

use Src\Category\Domain\ValueObjects\CategoryDescription;

test('ProductRepository saves and retrieves product with images and variants', function () {
    $category = $this->categoryRepository->create(Category::create(
        name: CategoryName::make('Tecnología'),
        slug: CategorySlug::fromString('tecnologia'),
        description: CategoryDescription::make('Dispositivos electrónicos')
    ));

    $brand = $this->brandRepository->save(Brand::create(
        name: BrandName::make('Sony'),
        slug: BrandSlug::fromString('sony')
    ));

    $image = ProductImage::create(
        imagePath: 'https://example.com/tv.jpg',
        altText: 'Smart TV Sony 55',
        isDefault: true,
        order: 0
    );

    $variant = ProductVariant::create(
        sku: 'SONY-TV-55',
        price: 599.99,
        quantity: 10,
        attributes: ['Tamaño' => '55 pulgadas']
    );

    $product = Product::create(
        name: ProductName::make('Smart TV Sony 4K 55"'),
        slug: ProductSlug::fromString('smart-tv-sony-4k-55'),
        sku: ProductSku::fromString('SONY-TV-4K-55'),
        price: ProductPrice::create(649.99, 799.99, 450.00),
        stock: ProductStock::create(15, 1, 50, true),
        dimensions: ProductDimensions::create(14.5, 75.0, 120.0, 10.0),
        status: ProductStatus::create(isVisible: true, isFeatured: true),
        description: ProductDescription::create('Televisor 4K HDR', 'Smart TV 55 pulgadas'),
        barcode: '7791234567890',
        categoryId: $category->getId()->value(),
        brandId: $brand->id()->value(),
        images: [$image],
        variants: [$variant]
    );

    $saved = $this->repository->save($product);

    expect($saved->id())->not->toBeNull()
        ->and($saved->name()->value())->toBe('Smart TV Sony 4K 55"')
        ->and($saved->sku()->value())->toBe('SONY-TV-4K-55')
        ->and($saved->price()->price())->toBe(649.99)
        ->and($saved->categoryName())->toBe('Tecnología')
        ->and($saved->brandName())->toBe('Sony')
        ->and($saved->images())->toHaveCount(1)
        ->and($saved->variants())->toHaveCount(1)
        ->and($saved->variants()[0]->sku())->toBe('SONY-TV-55');

    $foundById = $this->repository->findById($saved->id());
    expect($foundById)->not->toBeNull()
        ->and($foundById->sku()->value())->toBe('SONY-TV-4K-55');

    $foundBySlug = $this->repository->findBySlug(ProductSlug::fromString('smart-tv-sony-4k-55'));
    expect($foundBySlug)->not->toBeNull()
        ->and($foundBySlug->id()->value())->toBe($saved->id()->value());

    $foundBySku = $this->repository->findBySku(ProductSku::fromString('SONY-TV-4K-55'));
    expect($foundBySku)->not->toBeNull()
        ->and($foundBySku->id()->value())->toBe($saved->id()->value());
});

test('ProductRepository updates product details and relationships', function () {
    $product = Product::create(
        name: ProductName::make('Smartphone X'),
        slug: ProductSlug::fromString('smartphone-x'),
        sku: ProductSku::fromString('SM-X-001'),
        price: ProductPrice::create(300.00),
        stock: ProductStock::create(20)
    );

    $saved = $this->repository->save($product);

    $updatedProduct = new Product(
        id: $saved->id(),
        name: ProductName::make('Smartphone X Pro'),
        slug: ProductSlug::fromString('smartphone-x-pro'),
        sku: ProductSku::fromString('SM-X-PRO-001'),
        price: ProductPrice::create(350.00),
        stock: ProductStock::create(25),
        dimensions: ProductDimensions::create(0.2),
        status: ProductStatus::create(isVisible: false),
        description: ProductDescription::create('Versión Pro mejorada')
    );

    $updated = $this->repository->update($updatedProduct);

    expect($updated->name()->value())->toBe('Smartphone X Pro')
        ->and($updated->sku()->value())->toBe('SM-X-PRO-001')
        ->and($updated->price()->price())->toBe(350.00)
        ->and($updated->stock()->quantity())->toBe(25)
        ->and($updated->status()->isVisible())->toBeFalse();
});

test('ProductRepository deletes product with soft deletes', function () {
    $product = Product::create(
        name: ProductName::make('Producto a eliminar'),
        slug: ProductSlug::fromString('producto-a-eliminar'),
        sku: ProductSku::fromString('DEL-001'),
        price: ProductPrice::create(10.00),
        stock: ProductStock::create(5)
    );

    $saved = $this->repository->save($product);
    $this->repository->delete($saved->id());

    $found = $this->repository->findById($saved->id());
    expect($found)->toBeNull();
});

test('ProductRepository filters products by multiple criteria', function () {
    $category = $this->categoryRepository->create(Category::create(
        name: CategoryName::make('Calzado'),
        slug: CategorySlug::fromString('calzado'),
        description: CategoryDescription::make('Todo tipo de calzado')
    ));

    $prod1 = Product::create(
        name: ProductName::make('Zapatillas Running'),
        slug: ProductSlug::fromString('zapatillas-running'),
        sku: ProductSku::fromString('ZAP-RUN-01'),
        price: ProductPrice::create(80.00),
        stock: ProductStock::create(10),
        status: ProductStatus::create(isVisible: true, isFeatured: true),
        categoryId: $category->getId()->value()
    );

    $prod2 = Product::create(
        name: ProductName::make('Botas de Montaña'),
        slug: ProductSlug::fromString('botas-de-montana'),
        sku: ProductSku::fromString('BOT-MON-01'),
        price: ProductPrice::create(140.00),
        stock: ProductStock::create(0),
        status: ProductStatus::create(isVisible: false, isFeatured: false),
        categoryId: $category->getId()->value()
    );

    $this->repository->save($prod1);
    $this->repository->save($prod2);

    $searchResult = $this->repository->filter(new ProductFilterCriteria(search: 'Running'));
    expect($searchResult->total)->toBe(1)
        ->and($searchResult->items[0]->name()->value())->toBe('Zapatillas Running');

    $visibleResult = $this->repository->filter(new ProductFilterCriteria(isVisible: true));
    expect($visibleResult->total)->toBe(1);

    $priceResult = $this->repository->filter(new ProductFilterCriteria(minPrice: 100.00));
    expect($priceResult->total)->toBe(1)
        ->and($priceResult->items[0]->name()->value())->toBe('Botas de Montaña');

    $inStockResult = $this->repository->filter(new ProductFilterCriteria(inStock: true));
    expect($inStockResult->total)->toBe(1)
        ->and($inStockResult->items[0]->name()->value())->toBe('Zapatillas Running');
});

test('ProductRepository toggles visibility and updates stock directly', function () {
    $product = Product::create(
        name: ProductName::make('Mouse Óptico'),
        slug: ProductSlug::fromString('mouse-optico'),
        sku: ProductSku::fromString('MOU-OPT-01'),
        price: ProductPrice::create(15.00),
        stock: ProductStock::create(8),
        status: ProductStatus::create(isVisible: true)
    );

    $saved = $this->repository->save($product);

    $this->repository->toggleVisibility($saved->id(), false);
    $found = $this->repository->findById($saved->id());
    expect($found->status()->isVisible())->toBeFalse();

    $this->repository->updateStock($saved->id(), 20);
    $foundAfterStock = $this->repository->findById($saved->id());
    expect($foundAfterStock->stock()->quantity())->toBe(20);
});
