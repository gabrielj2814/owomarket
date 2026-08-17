<?php

declare(strict_types=1);

use Src\Attribute\Application\Contracts\AttributeRepositoryInterface;
use Src\Attribute\Application\DTOs\AttributeFilterCriteria;
use Src\Attribute\Application\DTOs\AttributeValueData;
use Src\Attribute\Application\DTOs\PaginatedAttributesResult;
use Src\Attribute\Application\UseCase\ConsultAttributeByIdUseCase;
use Src\Attribute\Application\UseCase\CreateAttributeUseCase;
use Src\Attribute\Application\UseCase\CreateAttributeValueUseCase;
use Src\Attribute\Application\UseCase\DeleteAttributeUseCase;
use Src\Attribute\Application\UseCase\DeleteAttributeValueUseCase;
use Src\Attribute\Application\UseCase\EditAttributeUseCase;
use Src\Attribute\Application\UseCase\FilterAttributesUseCase;
use Src\Attribute\Domain\Entities\ProductAttribute;
use Src\Attribute\Domain\Entities\ProductAttributeValue;
use Src\Attribute\Domain\Exceptions\AttributeNotFoundException;
use Src\Attribute\Domain\Exceptions\AttributeValueNotFoundException;
use Src\Attribute\Domain\ValueObjects\AttributeId;
use Src\Attribute\Domain\ValueObjects\AttributeName;
use Src\Attribute\Domain\ValueObjects\AttributeSlug;
use Src\Attribute\Domain\ValueObjects\AttributeType;
use Src\Attribute\Domain\ValueObjects\AttributeValueColor;
use Src\Attribute\Domain\ValueObjects\AttributeValueId;
use Src\Attribute\Domain\ValueObjects\AttributeValueImage;
use Src\Attribute\Domain\ValueObjects\AttributeValueText;

describe('ProductAttribute Use Cases Unit Tests', function () {
    test('CreateAttributeUseCase creates a new attribute and its values when slug is unique', function () {
        $repository = Mockery::mock(AttributeRepositoryInterface::class);

        $repository->shouldReceive('findBySlug')
            ->once()
            ->andReturnNull();

        $savedAttribute = new ProductAttribute(
            id: AttributeId::fromString('a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11'),
            name: AttributeName::make('Color'),
            slug: AttributeSlug::fromString('color'),
            type: AttributeType::color(),
            isFilterable: true,
            isVisible: true,
            position: 1
        );

        $repository->shouldReceive('save')
            ->once()
            ->andReturn($savedAttribute);

        $savedVal = new ProductAttributeValue(
            id: AttributeValueId::fromString('b0eebc99-9c0b-4ef8-bb6d-6bb9bd380a22'),
            attributeId: AttributeId::fromString('a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11'),
            value: AttributeValueText::make('Negro'),
            color: AttributeValueColor::fromNullableString('#000000'),
            image: AttributeValueImage::fromNullableString(null),
            position: 0
        );

        $repository->shouldReceive('saveValue')
            ->once()
            ->andReturn($savedVal);

        $useCase = new CreateAttributeUseCase($repository);
        $result = $useCase->execute(
            name: 'Color',
            slug: 'color',
            type: 'color',
            isFilterable: true,
            isVisible: true,
            position: 1,
            values: [
                new AttributeValueData(value: 'Negro', color: '#000000'),
            ]
        );

        expect($result->name()->value())->toBe('Color')
            ->and($result->slug()->value())->toBe('color')
            ->and(count($result->values()))->toBe(1);
    });

    test('CreateAttributeUseCase throws exception when slug already exists', function () {
        $repository = Mockery::mock(AttributeRepositoryInterface::class);

        $existing = new ProductAttribute(
            id: AttributeId::fromString('a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11'),
            name: AttributeName::make('Color'),
            slug: AttributeSlug::fromString('color'),
            type: AttributeType::color()
        );

        $repository->shouldReceive('findBySlug')
            ->once()
            ->andReturn($existing);

        $useCase = new CreateAttributeUseCase($repository);

        expect(fn () => $useCase->execute(name: 'Color', slug: 'color'))
            ->toThrow(InvalidArgumentException::class);
    });

    test('EditAttributeUseCase updates attribute details', function () {
        $repository = Mockery::mock(AttributeRepositoryInterface::class);

        $existing = new ProductAttribute(
            id: AttributeId::fromString('a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11'),
            name: AttributeName::make('Color'),
            slug: AttributeSlug::fromString('color'),
            type: AttributeType::select(),
            isFilterable: false,
            isVisible: true,
            position: 0
        );

        $repository->shouldReceive('findById')
            ->once()
            ->andReturn($existing);

        $repository->shouldReceive('findBySlug')
            ->once()
            ->andReturnNull();

        $repository->shouldReceive('update')
            ->once()
            ->andReturnUsing(fn (ProductAttribute $attr) => $attr);

        $useCase = new EditAttributeUseCase($repository);
        $result = $useCase->execute(
            id: 'a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11',
            name: 'Color Primario',
            slug: 'color-primario',
            type: 'color',
            isFilterable: true,
            isVisible: true,
            position: 2
        );

        expect($result->name()->value())->toBe('Color Primario')
            ->and($result->type()->value())->toBe('color')
            ->and($result->isFilterable())->toBeTrue();
    });

    test('ConsultAttributeByIdUseCase throws AttributeNotFoundException when not found', function () {
        $repository = Mockery::mock(AttributeRepositoryInterface::class);

        $repository->shouldReceive('findById')
            ->once()
            ->andReturnNull();

        $useCase = new ConsultAttributeByIdUseCase($repository);

        expect(fn () => $useCase->execute('a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a99'))
            ->toThrow(AttributeNotFoundException::class);
    });

    test('DeleteAttributeUseCase deletes existing attribute', function () {
        $repository = Mockery::mock(AttributeRepositoryInterface::class);

        $existing = new ProductAttribute(
            id: AttributeId::fromString('a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11'),
            name: AttributeName::make('Color'),
            slug: AttributeSlug::fromString('color'),
            type: AttributeType::color()
        );

        $repository->shouldReceive('findById')
            ->once()
            ->andReturn($existing);

        $repository->shouldReceive('delete')
            ->once()
            ->with(Mockery::on(fn (AttributeId $id) => $id->value() === 'a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11'));

        $useCase = new DeleteAttributeUseCase($repository);
        $useCase->execute('a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11');
        expect(true)->toBeTrue();
    });

    test('FilterAttributesUseCase returns paginated attributes result', function () {
        $repository = Mockery::mock(AttributeRepositoryInterface::class);
        $criteria = new AttributeFilterCriteria(search: 'Talla');

        $paginated = new PaginatedAttributesResult(
            items: [],
            total: 0,
            currentPage: 1,
            perPage: 10,
            lastPage: 1
        );

        $repository->shouldReceive('filter')
            ->once()
            ->with($criteria)
            ->andReturn($paginated);

        $useCase = new FilterAttributesUseCase($repository);
        $result = $useCase->execute($criteria);

        expect($result->total)->toBe(0);
    });

    test('CreateAttributeValueUseCase creates value for existing attribute', function () {
        $repository = Mockery::mock(AttributeRepositoryInterface::class);

        $existing = new ProductAttribute(
            id: AttributeId::fromString('a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11'),
            name: AttributeName::make('Talla'),
            slug: AttributeSlug::fromString('talla'),
            type: AttributeType::button()
        );

        $repository->shouldReceive('findById')
            ->once()
            ->andReturn($existing);

        $savedVal = new ProductAttributeValue(
            id: AttributeValueId::fromString('b0eebc99-9c0b-4ef8-bb6d-6bb9bd380a22'),
            attributeId: AttributeId::fromString('a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11'),
            value: AttributeValueText::make('XL'),
            color: AttributeValueColor::fromNullableString(null),
            image: AttributeValueImage::fromNullableString(null),
            position: 1
        );

        $repository->shouldReceive('saveValue')
            ->once()
            ->andReturn($savedVal);

        $useCase = new CreateAttributeValueUseCase($repository);
        $result = $useCase->execute(
            attributeId: 'a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11',
            value: 'XL',
            position: 1
        );

        expect($result->value()->value())->toBe('XL')
            ->and($result->position())->toBe(1);
    });

    test('DeleteAttributeValueUseCase throws exception when value not found', function () {
        $repository = Mockery::mock(AttributeRepositoryInterface::class);

        $repository->shouldReceive('findValueById')
            ->once()
            ->andReturnNull();

        $useCase = new DeleteAttributeValueUseCase($repository);

        expect(fn () => $useCase->execute('b0eebc99-9c0b-4ef8-bb6d-6bb9bd380a99'))
            ->toThrow(AttributeValueNotFoundException::class);
    });
});
