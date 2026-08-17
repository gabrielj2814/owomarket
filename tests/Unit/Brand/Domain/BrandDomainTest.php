<?php

declare(strict_types=1);

use Src\Brand\Domain\Entities\Brand;
use Src\Brand\Domain\Exceptions\InvalidBrandSlugException;
use Src\Brand\Domain\ValueObjects\BrandDescription;
use Src\Brand\Domain\ValueObjects\BrandId;
use Src\Brand\Domain\ValueObjects\BrandLogo;
use Src\Brand\Domain\ValueObjects\BrandName;
use Src\Brand\Domain\ValueObjects\BrandSlug;
use Src\Brand\Domain\ValueObjects\BrandStatus;

test('BrandName accepts valid names and rejects invalid names', function () {
    $valid = BrandName::make('Nike Official');
    expect($valid->value())->toBe('Nike Official');

    expect(fn () => BrandName::make('N'))
        ->toThrow(InvalidArgumentException::class);

    expect(fn () => BrandName::make(str_repeat('a', 151)))
        ->toThrow(InvalidArgumentException::class);
});

test('BrandSlug creates sanitized slugs correctly', function () {
    $slug = BrandSlug::fromString('Nike & Adidas Pro 2026!');
    expect($slug->value())->toBe('nike-adidas-pro-2026');

    expect(fn () => BrandSlug::fromString('   ---   '))
        ->toThrow(InvalidBrandSlugException::class);
});

test('BrandId correctly validates positive integer or null', function () {
    $id = new BrandId(5);
    expect($id->value())->toBe(5);

    $nullable = BrandId::fromNullableInt(null);
    expect($nullable)->toBeNull();

    expect(fn () => new BrandId(0))
        ->toThrow(InvalidArgumentException::class);
});

test('Brand entity creates and updates state cleanly without framework dependencies', function () {
    $brand = Brand::create(
        name: BrandName::make('Apple Inc'),
        slug: BrandSlug::fromString('apple-inc'),
        description: BrandDescription::fromNullableString('Tecnología premium'),
        logo: BrandLogo::fromNullableString('https://example.com/logo.png'),
        isActive: BrandStatus::active(),
        position: 1
    );

    expect($brand->name()->value())->toBe('Apple Inc');
    expect($brand->slug()->value())->toBe('apple-inc');
    expect($brand->description()->value())->toBe('Tecnología premium');
    expect($brand->logo()->value())->toBe('https://example.com/logo.png');
    expect($brand->isActive()->value())->toBeTrue();
    expect($brand->position())->toBe(1);

    $brand->changeName(BrandName::make('Apple Corp'));
    $brand->changeSlug(BrandSlug::fromString('apple-corp'));
    $brand->changeDescription(BrandDescription::fromNullableString('Actualizado'));
    $brand->changeLogo(BrandLogo::fromNullableString('https://example.com/new-logo.png'));
    $brand->deactivate();
    $brand->changePosition(5);

    expect($brand->name()->value())->toBe('Apple Corp');
    expect($brand->slug()->value())->toBe('apple-corp');
    expect($brand->description()->value())->toBe('Actualizado');
    expect($brand->logo()->value())->toBe('https://example.com/new-logo.png');
    expect($brand->isActive()->value())->toBeFalse();
    expect($brand->position())->toBe(5);

    $brand->activate();
    expect($brand->isActive()->value())->toBeTrue();
});
