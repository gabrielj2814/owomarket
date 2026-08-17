<?php

declare(strict_types=1);

use Src\Brand\Application\Contracts\BrandRepositoryInterface;
use Src\Brand\Application\DTOs\BrandFilterCriteria;
use Src\Brand\Application\DTOs\PaginatedBrandsResult;
use Src\Brand\Application\UseCase\ConsultBrandByIdUseCase;
use Src\Brand\Application\UseCase\CreateBrandUseCase;
use Src\Brand\Application\UseCase\DeleteBrandUseCase;
use Src\Brand\Application\UseCase\EditBrandUseCase;
use Src\Brand\Application\UseCase\FilterBrandsUseCase;
use Src\Brand\Application\UseCase\ListAllActiveBrandsUseCase;
use Src\Brand\Domain\Entities\Brand;
use Src\Brand\Domain\Exceptions\BrandNotFoundException;
use Src\Brand\Domain\ValueObjects\BrandDescription;
use Src\Brand\Domain\ValueObjects\BrandId;
use Src\Brand\Domain\ValueObjects\BrandLogo;
use Src\Brand\Domain\ValueObjects\BrandName;
use Src\Brand\Domain\ValueObjects\BrandSlug;
use Src\Brand\Domain\ValueObjects\BrandStatus;

test('CreateBrandUseCase creates a new brand when slug is unique', function () {
    $repository = Mockery::mock(BrandRepositoryInterface::class);

    $repository->shouldReceive('findBySlug')
        ->once()
        ->with(Mockery::on(fn (BrandSlug $slug) => $slug->value() === 'samsung'))
        ->andReturnNull();

    $repository->shouldReceive('save')
        ->once()
        ->with(Mockery::type(Brand::class))
        ->andReturnUsing(function (Brand $brand) {
            return new Brand(
                id: new BrandId(1),
                name: $brand->name(),
                slug: $brand->slug(),
                description: $brand->description(),
                logo: $brand->logo(),
                isActive: $brand->isActive(),
                position: $brand->position()
            );
        });

    $useCase = new CreateBrandUseCase($repository);

    $created = $useCase->execute(
        name: 'Samsung',
        slug: 'samsung',
        description: 'Electrónica y móviles',
        logo: 'https://example.com/samsung.png',
        isActive: true,
        position: 1
    );

    expect($created->id()->value())->toBe(1);
    expect($created->name()->value())->toBe('Samsung');
    expect($created->slug()->value())->toBe('samsung');
    expect($created->description()->value())->toBe('Electrónica y móviles');
});

test('CreateBrandUseCase throws exception when slug already exists', function () {
    $repository = Mockery::mock(BrandRepositoryInterface::class);

    $existing = new Brand(
        id: new BrandId(1),
        name: BrandName::make('Samsung'),
        slug: BrandSlug::fromString('samsung'),
        description: BrandDescription::fromNullableString(null),
        logo: BrandLogo::fromNullableString(null),
        isActive: BrandStatus::active()
    );

    $repository->shouldReceive('findBySlug')
        ->once()
        ->andReturn($existing);

    $useCase = new CreateBrandUseCase($repository);

    expect(fn () => $useCase->execute(name: 'Samsung', slug: 'samsung'))
        ->toThrow(InvalidArgumentException::class);
});

test('EditBrandUseCase updates brand details', function () {
    $repository = Mockery::mock(BrandRepositoryInterface::class);

    $brand = new Brand(
        id: new BrandId(1),
        name: BrandName::make('Sony Original'),
        slug: BrandSlug::fromString('sony-original'),
        description: BrandDescription::fromNullableString(null),
        logo: BrandLogo::fromNullableString(null),
        isActive: BrandStatus::active()
    );

    $repository->shouldReceive('findById')
        ->once()
        ->with(Mockery::on(fn (BrandId $id) => $id->value() === 1))
        ->andReturn($brand);

    $repository->shouldReceive('findBySlug')
        ->once()
        ->with(Mockery::on(fn (BrandSlug $slug) => $slug->value() === 'sony-updated'))
        ->andReturnNull();

    $repository->shouldReceive('update')
        ->once()
        ->with($brand)
        ->andReturn($brand);

    $useCase = new EditBrandUseCase($repository);

    $updated = $useCase->execute(
        id: 1,
        name: 'Sony Updated',
        slug: 'sony-updated',
        description: 'Nueva descripción',
        logo: 'https://example.com/sony.png',
        isActive: false,
        position: 2
    );

    expect($updated->name()->value())->toBe('Sony Updated');
    expect($updated->slug()->value())->toBe('sony-updated');
    expect($updated->description()->value())->toBe('Nueva descripción');
    expect($updated->isActive()->value())->toBeFalse();
    expect($updated->position())->toBe(2);
});

test('ConsultBrandByIdUseCase throws BrandNotFoundException when not found', function () {
    $repository = Mockery::mock(BrandRepositoryInterface::class);

    $repository->shouldReceive('findById')
        ->once()
        ->with(Mockery::on(fn (BrandId $id) => $id->value() === 99))
        ->andReturnNull();

    $useCase = new ConsultBrandByIdUseCase($repository);

    expect(fn () => $useCase->execute(99))
        ->toThrow(BrandNotFoundException::class);
});

test('DeleteBrandUseCase deletes existing brand', function () {
    $repository = Mockery::mock(BrandRepositoryInterface::class);

    $brand = new Brand(
        id: new BrandId(1),
        name: BrandName::make('ToDelete'),
        slug: BrandSlug::fromString('todelete'),
        description: BrandDescription::fromNullableString(null),
        logo: BrandLogo::fromNullableString(null),
        isActive: BrandStatus::active()
    );

    $repository->shouldReceive('findById')
        ->once()
        ->with(Mockery::on(fn (BrandId $id) => $id->value() === 1))
        ->andReturn($brand);

    $repository->shouldReceive('delete')
        ->once()
        ->with(Mockery::on(fn (BrandId $id) => $id->value() === 1));

    $useCase = new DeleteBrandUseCase($repository);
    $useCase->execute(1);

    expect(true)->toBeTrue();
});

test('FilterBrandsUseCase returns paginated brands result', function () {
    $repository = Mockery::mock(BrandRepositoryInterface::class);

    $criteria = new BrandFilterCriteria(search: 'LG');
    $paginatedResult = new PaginatedBrandsResult(
        items: [],
        total: 0,
        currentPage: 1,
        perPage: 10,
        lastPage: 1
    );

    $repository->shouldReceive('filter')
        ->once()
        ->with($criteria)
        ->andReturn($paginatedResult);

    $useCase = new FilterBrandsUseCase($repository);
    $result = $useCase->execute($criteria);

    expect($result->total)->toBe(0);
    expect($result->currentPage)->toBe(1);
});

test('ListAllActiveBrandsUseCase returns active brands list', function () {
    $repository = Mockery::mock(BrandRepositoryInterface::class);

    $activeBrands = [
        new Brand(
            id: new BrandId(1),
            name: BrandName::make('Nike'),
            slug: BrandSlug::fromString('nike'),
            description: BrandDescription::fromNullableString(null),
            logo: BrandLogo::fromNullableString(null),
            isActive: BrandStatus::active()
        ),
    ];

    $repository->shouldReceive('listAllActive')
        ->once()
        ->andReturn($activeBrands);

    $useCase = new ListAllActiveBrandsUseCase($repository);
    $result = $useCase->execute();

    expect($result)->toHaveCount(1);
    expect($result[0]->name()->value())->toBe('Nike');
});
