<?php

declare(strict_types=1);

use Src\Category\Application\Contracts\CategoryRepositoryInterface;
use Src\Category\Application\DTOs\CategoryFilterCriteria;
use Src\Category\Application\DTOs\PaginatedCategoriesResult;
use Src\Category\Application\UseCase\ConsultCategoryByIdUseCase;
use Src\Category\Application\UseCase\CreateCategoryUseCase;
use Src\Category\Application\UseCase\DeleteCategoryUseCase;
use Src\Category\Application\UseCase\EditCategoryUseCase;
use Src\Category\Application\UseCase\FilterCategoriesUseCase;
use Src\Category\Application\UseCase\ListCategoriesTreeUseCase;
use Src\Category\Domain\Entities\Category;
use Src\Category\Domain\Exceptions\CategoryNotFoundException;
use Src\Category\Domain\ValueObjects\CategoryDescription;
use Src\Category\Domain\ValueObjects\CategoryId;
use Src\Category\Domain\ValueObjects\CategoryName;
use Src\Category\Domain\ValueObjects\CategorySlug;
use Src\Category\Domain\ValueObjects\CategoryStatus;
use Src\Category\Domain\ValueObjects\ParentCategoryId;
use Tests\TestCase;

uses(TestCase::class);

test('CreateCategoryUseCase creates a new category when slug is unique', function () {
    $repo = Mockery::mock(CategoryRepositoryInterface::class);

    $repo->shouldReceive('findBySlug')
        ->once()
        ->andReturnNull();

    $repo->shouldReceive('create')
        ->once()
        ->andReturnUsing(function (Category $category) {
            return Category::reconstitute(
                id: CategoryId::fromInt(1),
                name: $category->getName(),
                slug: $category->getSlug(),
                description: $category->getDescription(),
                image: $category->getImage(),
                parentId: $category->getParentId(),
                isActive: $category->getIsActive(),
                position: $category->getPosition()
            );
        });

    $useCase = new CreateCategoryUseCase($repo);
    $created = $useCase->execute(
        name: 'Smartphones',
        slug: 'smartphones',
        description: 'Teléfonos móviles inteligentes',
        image: null,
        parentId: null,
        isActive: true,
        position: 0
    );

    expect($created->getId()->value())->toBe(1);
    expect($created->getName()->value())->toBe('Smartphones');
    expect($created->getSlug()->value())->toBe('smartphones');
});

test('CreateCategoryUseCase throws exception when slug already exists', function () {
    $repo = Mockery::mock(CategoryRepositoryInterface::class);

    $existing = Category::reconstitute(
        id: CategoryId::fromInt(2),
        name: CategoryName::make('Smartphones Existentes'),
        slug: CategorySlug::make('smartphones'),
        description: CategoryDescription::make(null),
        parentId: ParentCategoryId::null(),
        isActive: CategoryStatus::active()
    );

    $repo->shouldReceive('findBySlug')
        ->once()
        ->andReturn($existing);

    $useCase = new CreateCategoryUseCase($repo);

    expect(fn () => $useCase->execute(name: 'Smartphones', slug: 'smartphones'))
        ->toThrow(InvalidArgumentException::class);
});

test('EditCategoryUseCase updates category details and prevents category being its own parent', function () {
    $repo = Mockery::mock(CategoryRepositoryInterface::class);

    $existing = Category::reconstitute(
        id: CategoryId::fromInt(5),
        name: CategoryName::make('Nombre Antiguo'),
        slug: CategorySlug::make('nombre-antiguo'),
        description: CategoryDescription::make(null)
    );

    $repo->shouldReceive('findById')
        ->with(Mockery::on(fn (CategoryId $id) => $id->value() === 5))
        ->andReturn($existing);

    $repo->shouldReceive('findBySlug')
        ->with(Mockery::on(fn (CategorySlug $slug) => $slug->value() === 'nombre-nuevo'))
        ->andReturnNull();

    $repo->shouldReceive('edit')
        ->once()
        ->andReturnUsing(fn (Category $c) => $c);

    $useCase = new EditCategoryUseCase($repo);
    $updated = $useCase->execute(
        id: 5,
        name: 'Nombre Nuevo',
        slug: 'nombre-nuevo',
        description: 'Nueva descripción',
        image: null,
        parentId: null,
        isActive: true,
        position: 2
    );

    expect($updated->getName()->value())->toBe('Nombre Nuevo');
    expect($updated->getSlug()->value())->toBe('nombre-nuevo');
    expect($updated->getPosition())->toBe(2);

    // Prevent own parent
    expect(fn () => $useCase->execute(
        id: 5,
        name: 'Nombre',
        slug: 'nombre-nuevo',
        parentId: 5
    ))->toThrow(InvalidArgumentException::class);
});

test('ConsultCategoryByIdUseCase throws CategoryNotFoundException when not found', function () {
    $repo = Mockery::mock(CategoryRepositoryInterface::class);

    $repo->shouldReceive('findById')
        ->once()
        ->andReturnNull();

    $useCase = new ConsultCategoryByIdUseCase($repo);

    expect(fn () => $useCase->execute(999))->toThrow(CategoryNotFoundException::class);
});

test('DeleteCategoryUseCase deletes existing category', function () {
    $repo = Mockery::mock(CategoryRepositoryInterface::class);

    $existing = Category::reconstitute(
        id: CategoryId::fromInt(10),
        name: CategoryName::make('Categoría a borrar'),
        slug: CategorySlug::make('categoria-a-borrar'),
        description: CategoryDescription::make(null)
    );

    $repo->shouldReceive('findById')
        ->once()
        ->andReturn($existing);

    $repo->shouldReceive('delete')
        ->once()
        ->with(Mockery::on(fn (CategoryId $id) => $id->value() === 10));

    $useCase = new DeleteCategoryUseCase($repo);
    $useCase->execute(10);

    expect(true)->toBeTrue();
});

test('FilterCategoriesUseCase returns paginated categories result', function () {
    $repo = Mockery::mock(CategoryRepositoryInterface::class);

    $criteria = new CategoryFilterCriteria(search: 'Ropa');
    $sampleCategory = Category::reconstitute(
        id: CategoryId::fromInt(1),
        name: CategoryName::make('Ropa'),
        slug: CategorySlug::make('ropa'),
        description: CategoryDescription::make(null)
    );

    $repo->shouldReceive('filter')
        ->once()
        ->with($criteria)
        ->andReturn(new PaginatedCategoriesResult(
            items: [$sampleCategory],
            total: 1,
            currentPage: 1,
            perPage: 50,
            lastPage: 1
        ));

    $useCase = new FilterCategoriesUseCase($repo);
    $result = $useCase->execute($criteria);

    expect($result->total)->toBe(1);
    expect($result->items)->toHaveCount(1);
    expect($result->items[0]->getName()->value())->toBe('Ropa');
});

test('ListCategoriesTreeUseCase returns category tree from repository', function () {
    $repo = Mockery::mock(CategoryRepositoryInterface::class);

    $parentCategory = Category::reconstitute(
        id: CategoryId::fromInt(1),
        name: CategoryName::make('Moda'),
        slug: CategorySlug::make('moda'),
        description: CategoryDescription::make(null),
        children: [
            Category::reconstitute(
                id: CategoryId::fromInt(2),
                name: CategoryName::make('Ropa Hombre'),
                slug: CategorySlug::make('ropa-hombre'),
                description: CategoryDescription::make(null),
                parentId: ParentCategoryId::fromNullableInt(1)
            ),
        ]
    );

    $repo->shouldReceive('getTree')
        ->once()
        ->andReturn([$parentCategory]);

    $useCase = new ListCategoriesTreeUseCase($repo);
    $tree = $useCase->execute();

    expect($tree)->toHaveCount(1);
    expect($tree[0]->getChildren())->toHaveCount(1);
    expect($tree[0]->getChildren()[0]->getName()->value())->toBe('Ropa Hombre');
});
