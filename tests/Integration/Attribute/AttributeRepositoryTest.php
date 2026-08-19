<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schema;
use Src\Attribute\Application\DTOs\AttributeFilterCriteria;
use Src\Attribute\Domain\Entities\ProductAttribute;
use Src\Attribute\Domain\Entities\ProductAttributeValue;
use Src\Attribute\Domain\ValueObjects\AttributeName;
use Src\Attribute\Domain\ValueObjects\AttributeSlug;
use Src\Attribute\Domain\ValueObjects\AttributeType;
use Src\Attribute\Domain\ValueObjects\AttributeValueColor;
use Src\Attribute\Domain\ValueObjects\AttributeValueText;
use Src\Attribute\Infrastructure\Eloquent\Repositories\AttributeRepository;

beforeEach(function () {
    $migration1 = require base_path('database/migrations/tenant/2025_10_28_143325_create_product_attributes.php');
    if (! Schema::hasTable('product_attributes')) {
        $migration1->up();
    }

    $migration2 = require base_path('database/migrations/tenant/2025_10_28_143921_create_product_attribute_values.php');
    if (! Schema::hasTable('product_attribute_values')) {
        $migration2->up();
    }

    $this->repository = new AttributeRepository;
});

test('AttributeRepository creates and retrieves attribute by id and slug', function () {
    $attribute = ProductAttribute::create(
        name: AttributeName::make('Color'),
        slug: AttributeSlug::fromString('color'),
        type: AttributeType::color(),
        isFilterable: true,
        isVisible: true,
        position: 1
    );

    $saved = $this->repository->save($attribute);

    expect($saved->id())->not->toBeNull()
        ->and($saved->name()->value())->toBe('Color')
        ->and($saved->slug()->value())->toBe('color');

    $foundById = $this->repository->findById($saved->id());
    expect($foundById)->not->toBeNull()
        ->and($foundById->name()->value())->toBe('Color');

    $foundBySlug = $this->repository->findBySlug(AttributeSlug::fromString('color'));
    expect($foundBySlug)->not->toBeNull()
        ->and($foundBySlug->id()->value())->toBe($saved->id()->value());
});

test('AttributeRepository edits existing attribute', function () {
    $attribute = ProductAttribute::create(
        name: AttributeName::make('Talla Antigua'),
        slug: AttributeSlug::fromString('talla-antigua'),
        type: AttributeType::select(),
        isFilterable: false,
        isVisible: true,
        position: 0
    );

    $saved = $this->repository->save($attribute);

    $saved->changeName(AttributeName::make('Talla Nueva'));
    $saved->changeSlug(AttributeSlug::fromString('talla-nueva'));
    $saved->changeType(AttributeType::button());
    $saved->changeIsFilterable(true);
    $saved->changePosition(2);

    $updated = $this->repository->update($saved);

    expect($updated->name()->value())->toBe('Talla Nueva')
        ->and($updated->slug()->value())->toBe('talla-nueva')
        ->and($updated->type()->value())->toBe('button')
        ->and($updated->isFilterable())->toBeTrue()
        ->and($updated->position())->toBe(2);
});

test('AttributeRepository deletes attribute and its child values', function () {
    $attribute = $this->repository->save(ProductAttribute::create(
        name: AttributeName::make('Material'),
        slug: AttributeSlug::fromString('material')
    ));

    $val = $this->repository->saveValue(ProductAttributeValue::create(
        attributeId: $attribute->id(),
        value: AttributeValueText::make('Algodón')
    ));

    $this->repository->delete($attribute->id());

    $foundAttr = $this->repository->findById($attribute->id());
    expect($foundAttr)->toBeNull();

    $foundVal = $this->repository->findValueById($val->id());
    expect($foundVal)->toBeNull();
});

test('AttributeRepository filters attributes and lists with values', function () {
    $colorAttr = $this->repository->save(ProductAttribute::create(
        name: AttributeName::make('Color'),
        slug: AttributeSlug::fromString('color'),
        type: AttributeType::color(),
        isFilterable: true,
        isVisible: true,
        position: 1
    ));

    $this->repository->saveValue(ProductAttributeValue::create(
        attributeId: $colorAttr->id(),
        value: AttributeValueText::make('Negro'),
        color: AttributeValueColor::fromNullableString('#000000')
    ));

    $sizeAttr = $this->repository->save(ProductAttribute::create(
        name: AttributeName::make('Talla'),
        slug: AttributeSlug::fromString('talla'),
        type: AttributeType::button(),
        isFilterable: false,
        isVisible: true,
        position: 2
    ));

    $filter = $this->repository->filter(new AttributeFilterCriteria(
        search: 'Col',
        page: 1,
        perPage: 10
    ));

    expect($filter->total)->toBe(1)
        ->and($filter->items[0]->name()->value())->toBe('Color');

    $listWithVals = $this->repository->listWithValues();
    expect(count($listWithVals))->toBeGreaterThanOrEqual(2);
    $colorWithVals = collect($listWithVals)->first(fn ($a) => $a->name()->value() === 'Color');
    expect($colorWithVals)->not->toBeNull()
        ->and(count($colorWithVals->values()))->toBe(1);
});
