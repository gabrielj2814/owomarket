<?php

declare(strict_types=1);

use Src\Category\Domain\Entities\Category;
use Src\Category\Domain\Exceptions\InvalidCategorySlugException;
use Src\Category\Domain\ValueObjects\CategoryDescription;
use Src\Category\Domain\ValueObjects\CategoryId;
use Src\Category\Domain\ValueObjects\CategoryName;
use Src\Category\Domain\ValueObjects\CategorySlug;
use Src\Category\Domain\ValueObjects\CategoryStatus;
use Src\Category\Domain\ValueObjects\ParentCategoryId;
use Tests\TestCase;

uses(TestCase::class);

test('CategoryName accepts valid names and rejects invalid names', function () {
    $name = CategoryName::make('Electrónica y Tecnología');
    expect($name->value())->toBe('Electrónica y Tecnología');

    expect(fn () => CategoryName::make('A'))->toThrow(InvalidArgumentException::class);
    expect(fn () => CategoryName::make(str_repeat('A', 151)))->toThrow(InvalidArgumentException::class);
});

test('CategorySlug creates sanitized slugs correctly', function () {
    $slug = CategorySlug::fromString('Ropa y Calzado Deportivo');
    expect($slug->value())->toBe('ropa-y-calzado-deportivo');

    $directSlug = CategorySlug::make('hogar-y-cocina');
    expect($directSlug->value())->toBe('hogar-y-cocina');

    expect(fn () => CategorySlug::make('slug con espacios'))->toThrow(InvalidCategorySlugException::class);
});

test('CategoryId correctly validates positive integer or null', function () {
    $id = CategoryId::fromInt(5);
    expect($id->value())->toBe(5);
    expect($id->isNull())->toBeFalse();

    $nullId = CategoryId::null();
    expect($nullId->value())->toBeNull();
    expect($nullId->isNull())->toBeTrue();

    expect(fn () => CategoryId::fromInt(-1))->toThrow(InvalidArgumentException::class);
});

test('ParentCategoryId correctly validates parent id', function () {
    $parentId = ParentCategoryId::fromNullableInt(10);
    expect($parentId->value())->toBe(10);
    expect($parentId->isNull())->toBeFalse();

    $nullParent = ParentCategoryId::fromNullableInt(null);
    expect($nullParent->isNull())->toBeTrue();

    expect(fn () => ParentCategoryId::fromNullableInt(0))->toThrow(InvalidArgumentException::class);
});

test('Category entity creates and updates state cleanly without framework dependencies', function () {
    $category = Category::create(
        name: CategoryName::make('Calzado'),
        slug: CategorySlug::fromString('calzado'),
        description: CategoryDescription::make('Zapatos, tenis y botas'),
        image: 'https://example.com/calzado.jpg',
        parentId: ParentCategoryId::null(),
        isActive: CategoryStatus::active(),
        position: 1
    );

    expect($category->getName()->value())->toBe('Calzado');
    expect($category->getSlug()->value())->toBe('calzado');
    expect($category->getIsActive()->isActive())->toBeTrue();
    expect($category->getPosition())->toBe(1);

    $category->deactivate();
    expect($category->getIsActive()->isActive())->toBeFalse();

    $category->activate();
    expect($category->getIsActive()->isActive())->toBeTrue();

    $category->updateDetails(
        name: CategoryName::make('Calzado Deportivo'),
        slug: CategorySlug::fromString('calzado-deportivo'),
        description: CategoryDescription::make('Nueva descripción'),
        image: null,
        parentId: ParentCategoryId::fromNullableInt(2),
        isActive: CategoryStatus::active(),
        position: 3
    );

    expect($category->getName()->value())->toBe('Calzado Deportivo');
    expect($category->getSlug()->value())->toBe('calzado-deportivo');
    expect($category->getParentId()->value())->toBe(2);
    expect($category->getPosition())->toBe(3);

    $array = $category->toArray();
    expect($array)->toBeArray();
    expect($array['name'])->toBe('Calzado Deportivo');
    expect($array['slug'])->toBe('calzado-deportivo');
});
