<?php

declare(strict_types=1);

use Src\Product\Application\Contracts\ProductRepositoryInterface;
use Src\Product\Application\DTOs\PaginatedProductsResult;
use Src\Product\Application\DTOs\ProductFilterCriteria;
use Src\Product\Application\UseCase\ConsultProductByIdUseCase;
use Src\Product\Application\UseCase\ConsultProductBySlugUseCase;
use Src\Product\Application\UseCase\CreateProductUseCase;
use Src\Product\Application\UseCase\DeleteProductUseCase;
use Src\Product\Application\UseCase\EditProductUseCase;
use Src\Product\Application\UseCase\FilterProductsUseCase;
use Src\Product\Application\UseCase\ToggleProductVisibilityUseCase;
use Src\Product\Application\UseCase\UpdateProductStockUseCase;
use Src\Product\Domain\Entities\Product;
use Src\Product\Domain\Exceptions\ProductNotFoundException;
use Src\Product\Domain\Exceptions\ProductSkuAlreadyExistsException;
use Src\Product\Domain\Exceptions\ProductSlugAlreadyExistsException;
use Src\Product\Domain\ValueObjects\ProductId;
use Src\Product\Domain\ValueObjects\ProductName;
use Src\Product\Domain\ValueObjects\ProductPrice;
use Src\Product\Domain\ValueObjects\ProductSku;
use Src\Product\Domain\ValueObjects\ProductSlug;
use Src\Product\Domain\ValueObjects\ProductStatus;
use Src\Product\Domain\ValueObjects\ProductStock;

describe('Product Use Cases Unit Tests', function () {
    test('CreateProductUseCase creates product when slug and sku are unique', function () {
        $repository = Mockery::mock(ProductRepositoryInterface::class);

        $repository->shouldReceive('findBySlug')
            ->once()
            ->andReturnNull();

        $repository->shouldReceive('findBySku')
            ->once()
            ->andReturnNull();

        $dummyProduct = new Product(
            id: ProductId::fromString('018f3a9e-8c7a-7b3b-9a4a-1a2b3c4d5e6f'),
            name: ProductName::make('Auriculares Bluetooth'),
            slug: ProductSlug::fromString('auriculares-bluetooth'),
            sku: ProductSku::fromString('AUD-BT-001'),
            price: ProductPrice::create(49.99),
            stock: ProductStock::create(50)
        );

        $repository->shouldReceive('save')
            ->once()
            ->andReturn($dummyProduct);

        $useCase = new CreateProductUseCase($repository);
        $result = $useCase->execute(
            name: 'Auriculares Bluetooth',
            slug: 'auriculares-bluetooth',
            sku: 'AUD-BT-001',
            price: 49.99,
            quantity: 50
        );

        expect($result->name()->value())->toBe('Auriculares Bluetooth')
            ->and($result->sku()->value())->toBe('AUD-BT-001')
            ->and($result->price()->price())->toBe(49.99);
    });

    test('CreateProductUseCase throws exception on duplicate slug', function () {
        $repository = Mockery::mock(ProductRepositoryInterface::class);

        $dummyProduct = new Product(
            id: ProductId::fromString('018f3a9e-8c7a-7b3b-9a4a-1a2b3c4d5e6f'),
            name: ProductName::make('Auriculares Bluetooth'),
            slug: ProductSlug::fromString('auriculares-bluetooth'),
            sku: ProductSku::fromString('AUD-BT-001'),
            price: ProductPrice::create(49.99),
            stock: ProductStock::create(50)
        );

        $repository->shouldReceive('findBySlug')
            ->once()
            ->andReturn($dummyProduct);

        $useCase = new CreateProductUseCase($repository);

        expect(fn () => $useCase->execute(
            name: 'Auriculares Bluetooth',
            slug: 'auriculares-bluetooth',
            sku: 'AUD-BT-002',
            price: 49.99,
            quantity: 50
        ))->toThrow(ProductSlugAlreadyExistsException::class);
    });

    test('CreateProductUseCase throws exception on duplicate SKU', function () {
        $repository = Mockery::mock(ProductRepositoryInterface::class);

        $dummyProduct = new Product(
            id: ProductId::fromString('018f3a9e-8c7a-7b3b-9a4a-1a2b3c4d5e6f'),
            name: ProductName::make('Auriculares Bluetooth'),
            slug: ProductSlug::fromString('auriculares-bluetooth'),
            sku: ProductSku::fromString('AUD-BT-001'),
            price: ProductPrice::create(49.99),
            stock: ProductStock::create(50)
        );

        $repository->shouldReceive('findBySlug')
            ->once()
            ->andReturnNull();

        $repository->shouldReceive('findBySku')
            ->once()
            ->andReturn($dummyProduct);

        $useCase = new CreateProductUseCase($repository);

        expect(fn () => $useCase->execute(
            name: 'Auriculares Bluetooth Nuevo',
            slug: 'auriculares-bluetooth-nuevo',
            sku: 'AUD-BT-001',
            price: 49.99,
            quantity: 50
        ))->toThrow(ProductSkuAlreadyExistsException::class);
    });

    test('EditProductUseCase updates product and validates conflicts', function () {
        $repository = Mockery::mock(ProductRepositoryInterface::class);
        $productId = '018f3a9e-8c7a-7b3b-9a4a-1a2b3c4d5e6f';

        $existingProduct = new Product(
            id: ProductId::fromString($productId),
            name: ProductName::make('Teclado Mecánico'),
            slug: ProductSlug::fromString('teclado-mecanico'),
            sku: ProductSku::fromString('TEC-MEC-001'),
            price: ProductPrice::create(80.00),
            stock: ProductStock::create(20)
        );

        $repository->shouldReceive('findById')
            ->once()
            ->andReturn($existingProduct);

        $repository->shouldReceive('findBySlug')
            ->once()
            ->andReturn($existingProduct);

        $repository->shouldReceive('findBySku')
            ->once()
            ->andReturn($existingProduct);

        $updatedProduct = new Product(
            id: ProductId::fromString($productId),
            name: ProductName::make('Teclado Mecánico RGB'),
            slug: ProductSlug::fromString('teclado-mecanico'),
            sku: ProductSku::fromString('TEC-MEC-001'),
            price: ProductPrice::create(95.00),
            stock: ProductStock::create(30)
        );

        $repository->shouldReceive('update')
            ->once()
            ->andReturn($updatedProduct);

        $useCase = new EditProductUseCase($repository);
        $result = $useCase->execute(
            id: $productId,
            name: 'Teclado Mecánico RGB',
            slug: 'teclado-mecanico',
            sku: 'TEC-MEC-001',
            price: 95.00,
            quantity: 30
        );

        expect($result->name()->value())->toBe('Teclado Mecánico RGB')
            ->and($result->price()->price())->toBe(95.00);
    });

    test('ConsultProductByIdUseCase returns product or throws exception', function () {
        $repository = Mockery::mock(ProductRepositoryInterface::class);
        $productId = '018f3a9e-8c7a-7b3b-9a4a-1a2b3c4d5e6f';

        $product = new Product(
            id: ProductId::fromString($productId),
            name: ProductName::make('Monitor Gamer'),
            slug: ProductSlug::fromString('monitor-gamer'),
            sku: ProductSku::fromString('MON-GAM-001'),
            price: ProductPrice::create(250.00),
            stock: ProductStock::create(10)
        );

        $repository->shouldReceive('findById')
            ->once()
            ->andReturn($product);

        $useCase = new ConsultProductByIdUseCase($repository);
        $result = $useCase->execute($productId);
        expect($result->name()->value())->toBe('Monitor Gamer');

        $repository->shouldReceive('findById')
            ->once()
            ->andReturnNull();

        expect(fn () => $useCase->execute('018f3a9e-8c7a-7b3b-9a4a-1a2b3c4d9999'))
            ->toThrow(ProductNotFoundException::class);
    });

    test('ConsultProductBySlugUseCase returns product or throws exception', function () {
        $repository = Mockery::mock(ProductRepositoryInterface::class);

        $product = new Product(
            id: ProductId::fromString('018f3a9e-8c7a-7b3b-9a4a-1a2b3c4d5e6f'),
            name: ProductName::make('Mouse Inalámbrico'),
            slug: ProductSlug::fromString('mouse-inalambrico'),
            sku: ProductSku::fromString('MOU-INA-001'),
            price: ProductPrice::create(25.00),
            stock: ProductStock::create(15)
        );

        $repository->shouldReceive('findBySlug')
            ->once()
            ->andReturn($product);

        $useCase = new ConsultProductBySlugUseCase($repository);
        $result = $useCase->execute('mouse-inalambrico');
        expect($result->name()->value())->toBe('Mouse Inalámbrico');

        $repository->shouldReceive('findBySlug')
            ->once()
            ->andReturnNull();

        expect(fn () => $useCase->execute('no-existe'))
            ->toThrow(ProductNotFoundException::class);
    });

    test('DeleteProductUseCase deletes product when found', function () {
        $repository = Mockery::mock(ProductRepositoryInterface::class);
        $productId = '018f3a9e-8c7a-7b3b-9a4a-1a2b3c4d5e6f';

        $product = new Product(
            id: ProductId::fromString($productId),
            name: ProductName::make('Silla Ergonómica'),
            slug: ProductSlug::fromString('silla-ergonomica'),
            sku: ProductSku::fromString('SIL-ERG-001'),
            price: ProductPrice::create(180.00),
            stock: ProductStock::create(8)
        );

        $repository->shouldReceive('findById')
            ->once()
            ->andReturn($product);

        $repository->shouldReceive('delete')
            ->once();

        $useCase = new DeleteProductUseCase($repository);
        $useCase->execute($productId);
        expect(true)->toBeTrue();
    });

    test('FilterProductsUseCase delegates to repository', function () {
        $repository = Mockery::mock(ProductRepositoryInterface::class);
        $criteria = new ProductFilterCriteria(search: 'Gamer');

        $repository->shouldReceive('filter')
            ->once()
            ->with($criteria)
            ->andReturn(new PaginatedProductsResult([], 0, 1, 10, 1));

        $useCase = new FilterProductsUseCase($repository);
        $result = $useCase->execute($criteria);

        expect($result->total)->toBe(0);
    });

    test('ToggleProductVisibilityUseCase toggles or updates visibility', function () {
        $repository = Mockery::mock(ProductRepositoryInterface::class);
        $productId = '018f3a9e-8c7a-7b3b-9a4a-1a2b3c4d5e6f';

        $product = new Product(
            id: ProductId::fromString($productId),
            name: ProductName::make('Tablet Pro'),
            slug: ProductSlug::fromString('tablet-pro'),
            sku: ProductSku::fromString('TAB-PRO-001'),
            price: ProductPrice::create(350.00),
            stock: ProductStock::create(12),
            status: ProductStatus::create(isVisible: true)
        );

        $repository->shouldReceive('findById')
            ->once()
            ->andReturn($product);

        $repository->shouldReceive('toggleVisibility')
            ->once()
            ->with(Mockery::type(ProductId::class), false);

        $useCase = new ToggleProductVisibilityUseCase($repository);
        $useCase->execute($productId);
        expect(true)->toBeTrue();
    });

    test('UpdateProductStockUseCase updates stock in repository', function () {
        $repository = Mockery::mock(ProductRepositoryInterface::class);
        $productId = '018f3a9e-8c7a-7b3b-9a4a-1a2b3c4d5e6f';

        $product = new Product(
            id: ProductId::fromString($productId),
            name: ProductName::make('Tablet Pro'),
            slug: ProductSlug::fromString('tablet-pro'),
            sku: ProductSku::fromString('TAB-PRO-001'),
            price: ProductPrice::create(350.00),
            stock: ProductStock::create(12)
        );

        $repository->shouldReceive('findById')
            ->once()
            ->andReturn($product);

        $repository->shouldReceive('updateStock')
            ->once()
            ->with(Mockery::type(ProductId::class), 25);

        $useCase = new UpdateProductStockUseCase($repository);
        $useCase->execute($productId, 25);
        expect(true)->toBeTrue();
    });
});
