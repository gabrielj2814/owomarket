<?php

declare(strict_types=1);

namespace Src\Attribute\Application\UseCase;

use InvalidArgumentException;
use Src\Attribute\Application\Contracts\AttributeRepositoryInterface;
use Src\Attribute\Application\DTOs\AttributeValueData;
use Src\Attribute\Domain\Entities\ProductAttribute;
use Src\Attribute\Domain\Entities\ProductAttributeValue;
use Src\Attribute\Domain\ValueObjects\AttributeName;
use Src\Attribute\Domain\ValueObjects\AttributeSlug;
use Src\Attribute\Domain\ValueObjects\AttributeType;
use Src\Attribute\Domain\ValueObjects\AttributeValueColor;
use Src\Attribute\Domain\ValueObjects\AttributeValueImage;
use Src\Attribute\Domain\ValueObjects\AttributeValueText;

final class CreateAttributeUseCase
{
    public function __construct(
        private readonly AttributeRepositoryInterface $repository
    ) {}

    /**
     * @param  AttributeValueData[]  $values
     */
    public function execute(
        string $name,
        ?string $slug = null,
        string $type = 'select',
        bool $isFilterable = false,
        bool $isVisible = true,
        int $position = 0,
        array $values = []
    ): ProductAttribute {
        $attributeName = AttributeName::make($name);
        $attributeSlug = AttributeSlug::fromString($slug && trim($slug) !== '' ? $slug : $name);
        $attributeType = AttributeType::fromString($type);

        $existing = $this->repository->findBySlug($attributeSlug);
        if ($existing !== null) {
            throw new InvalidArgumentException(
                sprintf('Ya existe un atributo con el slug "%s".', $attributeSlug->value())
            );
        }

        $attribute = ProductAttribute::create(
            name: $attributeName,
            slug: $attributeSlug,
            type: $attributeType,
            isFilterable: $isFilterable,
            isVisible: $isVisible,
            position: $position
        );

        $savedAttribute = $this->repository->save($attribute);

        if (! empty($values) && $savedAttribute->id() !== null) {
            $createdValues = [];
            foreach ($values as $valData) {
                $attrValue = ProductAttributeValue::create(
                    attributeId: $savedAttribute->id(),
                    value: AttributeValueText::make($valData->value),
                    color: AttributeValueColor::fromNullableString($valData->color),
                    image: AttributeValueImage::fromNullableString($valData->image),
                    position: $valData->position
                );
                $createdValues[] = $this->repository->saveValue($attrValue);
            }
            $savedAttribute->setValues($createdValues);
        }

        return $savedAttribute;
    }
}
