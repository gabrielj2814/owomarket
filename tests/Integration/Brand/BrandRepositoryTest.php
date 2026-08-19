<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schema;
use Src\Brand\Application\DTOs\BrandFilterCriteria;
use Src\Brand\Domain\Entities\Brand;
use Src\Brand\Domain\ValueObjects\BrandDescription;
use Src\Brand\Domain\ValueObjects\BrandLogo;
use Src\Brand\Domain\ValueObjects\BrandName;
use Src\Brand\Domain\ValueObjects\BrandSlug;
use Src\Brand\Domain\ValueObjects\BrandStatus;
use Src\Brand\Infrastructure\Eloquent\Repositories\BrandRepository;

beforeEach(function () {
    $migration = require base_path('database/migrations/tenant/2025_10_28_143000_create_brands.php');
    if (! Schema::hasTable('brands')) {
        $migration->up();
    }
    $this->repository = new BrandRepository;
});

test('BrandRepository creates and retrieves brand by id and slug', function () {
    $brand = Brand::create(
        name: BrandName::make('Puma'),
        slug: BrandSlug::fromString('puma'),
        description: BrandDescription::fromNullableString('Indumentaria deportiva'),
        logo: BrandLogo::fromNullableString('https://example.com/puma.png'),
        isActive: BrandStatus::active(),
        position: 1
    );

    $saved = $this->repository->save($brand);

    expect($saved->id())->not->toBeNull();
    expect($saved->name()->value())->toBe('Puma');
    expect($saved->slug()->value())->toBe('puma');

    $foundById = $this->repository->findById($saved->id());
    expect($foundById)->not->toBeNull();
    expect($foundById->name()->value())->toBe('Puma');

    $foundBySlug = $this->repository->findBySlug(BrandSlug::fromString('puma'));
    expect($foundBySlug)->not->toBeNull();
    expect($foundBySlug->id()->value())->toBe($saved->id()->value());
});

test('BrandRepository edits existing brand', function () {
    $brand = Brand::create(
        name: BrandName::make('Adidas Original'),
        slug: BrandSlug::fromString('adidas-original'),
        description: null,
        logo: null,
        isActive: BrandStatus::active(),
        position: 0
    );

    $saved = $this->repository->save($brand);

    $saved->changeName(BrandName::make('Adidas Performance'));
    $saved->changeSlug(BrandSlug::fromString('adidas-performance'));
    $saved->changeDescription(BrandDescription::fromNullableString('Nueva línea'));
    $saved->changeLogo(BrandLogo::fromNullableString('https://example.com/adidas.png'));
    $saved->deactivate();
    $saved->changePosition(3);

    $updated = $this->repository->update($saved);

    expect($updated->name()->value())->toBe('Adidas Performance');
    expect($updated->slug()->value())->toBe('adidas-performance');
    expect($updated->description()->value())->toBe('Nueva línea');
    expect($updated->logo()->value())->toBe('https://example.com/adidas.png');
    expect($updated->isActive()->value())->toBeFalse();
    expect($updated->position())->toBe(3);
});

test('BrandRepository deletes brand', function () {
    $brand = Brand::create(
        name: BrandName::make('Reebok'),
        slug: BrandSlug::fromString('reebok')
    );

    $saved = $this->repository->save($brand);
    $this->repository->delete($saved->id());

    $found = $this->repository->findById($saved->id());
    expect($found)->toBeNull();
});

test('BrandRepository filters brands with search, active status and pagination', function () {
    $this->repository->save(Brand::create(
        name: BrandName::make('Under Armour'),
        slug: BrandSlug::fromString('under-armour'),
        description: BrandDescription::fromNullableString('Ropa deportiva'),
        isActive: BrandStatus::active()
    ));

    $this->repository->save(Brand::create(
        name: BrandName::make('Zara'),
        slug: BrandSlug::fromString('zara'),
        description: BrandDescription::fromNullableString('Moda urbana'),
        isActive: BrandStatus::inactive()
    ));

    $filterSearch = $this->repository->filter(new BrandFilterCriteria(
        search: 'Under',
        page: 1,
        perPage: 10
    ));
    expect($filterSearch->total)->toBe(1);
    expect($filterSearch->items[0]->name()->value())->toBe('Under Armour');

    $filterActive = $this->repository->filter(new BrandFilterCriteria(
        isActive: true,
        page: 1,
        perPage: 10
    ));
    expect($filterActive->total)->toBe(1);
    expect($filterActive->items[0]->name()->value())->toBe('Under Armour');
});

test('BrandRepository listAllActive returns only active brands sorted by position and name', function () {
    $this->repository->save(Brand::create(
        name: BrandName::make('Brand B'),
        slug: BrandSlug::fromString('brand-b'),
        isActive: BrandStatus::active(),
        position: 2
    ));

    $this->repository->save(Brand::create(
        name: BrandName::make('Brand A'),
        slug: BrandSlug::fromString('brand-a'),
        isActive: BrandStatus::active(),
        position: 1
    ));

    $this->repository->save(Brand::create(
        name: BrandName::make('Brand Inactive'),
        slug: BrandSlug::fromString('brand-inactive'),
        isActive: BrandStatus::inactive()
    ));

    $activeList = $this->repository->listAllActive();

    expect($activeList)->toHaveCount(2);
    expect($activeList[0]->name()->value())->toBe('Brand A');
    expect($activeList[1]->name()->value())->toBe('Brand B');
});
