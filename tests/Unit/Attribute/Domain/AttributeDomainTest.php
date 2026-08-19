<?php

declare(strict_types=1);

use Src\Attribute\Domain\Entities\ProductAttribute;
use Src\Attribute\Domain\Entities\ProductAttributeValue;
use Src\Attribute\Domain\Exceptions\InvalidAttributeSlugException;
use Src\Attribute\Domain\ValueObjects\AttributeId;
use Src\Attribute\Domain\ValueObjects\AttributeName;
use Src\Attribute\Domain\ValueObjects\AttributeSlug;
use Src\Attribute\Domain\ValueObjects\AttributeType;
use Src\Attribute\Domain\ValueObjects\AttributeValueColor;
use Src\Attribute\Domain\ValueObjects\AttributeValueImage;
use Src\Attribute\Domain\ValueObjects\AttributeValueText;

describe('ProductAttribute Domain Unit Tests', function () {
    test('AttributeName accepts valid names and rejects invalid names', function () {
        $name = AttributeName::make('Color');
        expect($name->value())->toBe('Color');

        expect(fn () => AttributeName::make('A'))
            ->toThrow(InvalidArgumentException::class);
    });

    test('AttributeSlug creates sanitized slugs correctly', function () {
        $slug = AttributeSlug::fromString('Talla de Ropa & Calzado!');
        expect($slug->value())->toBe('talla-de-ropa-calzado');

        expect(fn () => AttributeSlug::fromString(''))
            ->toThrow(InvalidAttributeSlugException::class);
    });

    test('AttributeType validates allowed types correctly', function () {
        $select = AttributeType::select();
        expect($select->value())->toBe('select');

        $color = AttributeType::fromString('color');
        expect($color->value())->toBe('color');

        expect(fn () => AttributeType::fromString('invalid_type'))
            ->toThrow(InvalidArgumentException::class);
    });

    test('AttributeId correctly validates UUID string format or null', function () {
        $validUuid = 'a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11';
        $id = AttributeId::fromString($validUuid);
        expect($id->value())->toBe($validUuid)
            ->and($id->isNull())->toBeFalse();

        $nullId = AttributeId::null();
        expect($nullId->value())->toBeNull()
            ->and($nullId->isNull())->toBeTrue();
    });

    test('ProductAttribute entity creates and updates state cleanly without framework dependencies', function () {
        $attribute = ProductAttribute::create(
            name: AttributeName::make('Talla'),
            slug: AttributeSlug::fromString('talla'),
            type: AttributeType::button(),
            isFilterable: true,
            isVisible: true,
            position: 1
        );

        expect($attribute->name()->value())->toBe('Talla')
            ->and($attribute->slug()->value())->toBe('talla')
            ->and($attribute->type()->value())->toBe('button')
            ->and($attribute->isFilterable())->toBeTrue()
            ->and($attribute->isVisible())->toBeTrue()
            ->and($attribute->position())->toBe(1);

        $attribute->changeName(AttributeName::make('Talla Internacional'));
        $attribute->changeSlug(AttributeSlug::fromString('talla-internacional'));
        $attribute->changeType(AttributeType::select());
        $attribute->changeIsFilterable(false);
        $attribute->changePosition(5);

        expect($attribute->name()->value())->toBe('Talla Internacional')
            ->and($attribute->slug()->value())->toBe('talla-internacional')
            ->and($attribute->type()->value())->toBe('select')
            ->and($attribute->isFilterable())->toBeFalse()
            ->and($attribute->position())->toBe(5);
    });

    test('ProductAttributeValue entity creates and serializes correctly', function () {
        $attrId = AttributeId::fromString('a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11');
        $val = ProductAttributeValue::create(
            attributeId: $attrId,
            value: AttributeValueText::make('Rojo Pasión'),
            color: AttributeValueColor::fromNullableString('#FF0000'),
            image: AttributeValueImage::fromNullableString('https://example.com/red.png'),
            position: 2
        );

        expect($val->attributeId()->value())->toBe('a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11')
            ->and($val->value()->value())->toBe('Rojo Pasión')
            ->and($val->color()->value())->toBe('#FF0000')
            ->and($val->image()->value())->toBe('https://example.com/red.png')
            ->and($val->position())->toBe(2);

        $array = $val->toArray();
        expect($array['value'])->toBe('Rojo Pasión')
            ->and($array['color'])->toBe('#FF0000');
    });
});
