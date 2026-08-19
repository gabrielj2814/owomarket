<?php

declare(strict_types=1);

namespace Src\Attribute\Application\UseCase;

use Src\Attribute\Application\Contracts\AttributeRepositoryInterface;
use Src\Attribute\Domain\Entities\ProductAttributeValue;
use Src\Attribute\Domain\Exceptions\AttributeNotFoundException;
use Src\Attribute\Domain\ValueObjects\AttributeId;
use Src\Attribute\Domain\ValueObjects\AttributeValueColor;
use Src\Attribute\Domain\ValueObjects\AttributeValueImage;
use Src\Attribute\Domain\ValueObjects\AttributeValueText;

final class CreateAttributeValueUseCase
{
    public function __construct(
        private readonly AttributeRepositoryInterface $repository
    ) {}

    public function execute(
        string $attributeId,
        string $value,
        ?string $color = null,
        ?string $image = null,
        int $position = 0
    ): ProductAttributeValue {
        $attrId = AttributeId::fromString($attributeId);
        $attribute = $this->repository->findById($attrId);

        if ($attribute === null) {
            throw new AttributeNotFoundException($attributeId);
        }

        $attributeValue = ProductAttributeValue::create(
            attributeId: $attrId,
            value: AttributeValueText::make($value),
            color: AttributeValueColor::fromNullableString($color),
            image: AttributeValueImage::fromNullableString($image),
            position: $position
        );

        return $this->repository->saveValue($attributeValue);
    }
}
