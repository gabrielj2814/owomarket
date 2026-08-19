<?php

declare(strict_types=1);

use Src\Category\Application\DTOs\CategoryFilterCriteria;
use Src\Category\Domain\Entities\Category;
use Src\Category\Domain\ValueObjects\CategoryDescription;
use Src\Category\Domain\ValueObjects\CategoryName;
use Src\Category\Domain\ValueObjects\CategorySlug;
use Src\Category\Domain\ValueObjects\CategoryStatus;
use Src\Category\Domain\ValueObjects\ParentCategoryId;
use Src\Category\Infrastructure\Eloquent\Repositories\CategoryRepository;

beforeEach(function () {
    $migration = require base_path('database/migrations/tenant/2025_10_28_142911_create_categories.php');
    $migration->up();
    $this->repository = new CategoryRepository;
});

test('CategoryRepository creates and retrieves category by id and slug', function () {
    $category = Category::create(
        name: CategoryName::make('Tecnología'),
        slug: CategorySlug::fromString('tecnologia'),
        description: CategoryDescription::make('Dispositivos electrónicos'),
        image: 'https://example.com/tech.jpg',
        parentId: null,
        isActive: CategoryStatus::active(),
        position: 1
    );

    $saved = $this->repository->create($category);
    expect($saved->getId()->value())->toBeInt();

    $foundById = $this->repository->findById($saved->getId());
    expect($foundById)->not->toBeNull();
    expect($foundById->getName()->value())->toBe('Tecnología');
    expect($foundById->getSlug()->value())->toBe('tecnologia');

    $foundBySlug = $this->repository->findBySlug(CategorySlug::make('tecnologia'));
    expect($foundBySlug)->not->toBeNull();
    expect($foundBySlug->getId()->value())->toBe($saved->getId()->value());
});

test('CategoryRepository edits existing category', function () {
    $category = Category::create(
        name: CategoryName::make('Hogar'),
        slug: CategorySlug::fromString('hogar'),
        description: CategoryDescription::make('Cosas de casa')
    );

    $saved = $this->repository->create($category);

    $saved->updateDetails(
        name: CategoryName::make('Hogar y Muebles'),
        slug: CategorySlug::fromString('hogar-y-muebles'),
        description: CategoryDescription::make('Muebles y decoración'),
        image: null,
        parentId: ParentCategoryId::null(),
        isActive: CategoryStatus::inactive(),
        position: 5
    );

    $updated = $this->repository->edit($saved);

    expect($updated->getName()->value())->toBe('Hogar y Muebles');
    expect($updated->getSlug()->value())->toBe('hogar-y-muebles');
    expect($updated->getIsActive()->isActive())->toBeFalse();
    expect($updated->getPosition())->toBe(5);
});

test('CategoryRepository deletes category', function () {
    $category = Category::create(
        name: CategoryName::make('Temporal'),
        slug: CategorySlug::fromString('temporal'),
        description: CategoryDescription::make(null)
    );

    $saved = $this->repository->create($category);
    $this->repository->delete($saved->getId());

    $found = $this->repository->findById($saved->getId());
    expect($found)->toBeNull();
});

test('CategoryRepository filters categories with search, active status and pagination', function () {
    $cat1 = Category::create(
        name: CategoryName::make('Computación'),
        slug: CategorySlug::fromString('computacion'),
        description: CategoryDescription::make(null),
        isActive: CategoryStatus::active()
    );
    $cat2 = Category::create(
        name: CategoryName::make('Audio y Video'),
        slug: CategorySlug::fromString('audio-y-video'),
        description: CategoryDescription::make(null),
        isActive: CategoryStatus::inactive()
    );

    $this->repository->create($cat1);
    $this->repository->create($cat2);

    $criteria = new CategoryFilterCriteria(search: 'computacion', page: 1, perPage: 10);
    $result = $this->repository->filter($criteria);

    expect($result->total)->toBe(1);
    expect($result->items[0]->getName()->value())->toBe('Computación');

    $activeOnly = new CategoryFilterCriteria(isActive: true);
    $activeResult = $this->repository->filter($activeOnly);
    expect($activeResult->total)->toBe(1);
});

test('CategoryRepository returns hierarchical tree of categories', function () {
    $parent = Category::create(
        name: CategoryName::make('Ropa'),
        slug: CategorySlug::fromString('ropa'),
        description: CategoryDescription::make(null)
    );
    $savedParent = $this->repository->create($parent);

    $child = Category::create(
        name: CategoryName::make('Camisas'),
        slug: CategorySlug::fromString('camisas'),
        description: CategoryDescription::make(null),
        parentId: ParentCategoryId::fromNullableInt($savedParent->getId()->value())
    );
    $this->repository->create($child);

    $tree = $this->repository->getTree();
    expect($tree)->toHaveCount(1);
    expect($tree[0]->getName()->value())->toBe('Ropa');
    expect($tree[0]->getChildren())->toHaveCount(1);
    expect($tree[0]->getChildren()[0]->getName()->value())->toBe('Camisas');
});
