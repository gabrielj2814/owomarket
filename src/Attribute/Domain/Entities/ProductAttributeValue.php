<?php

declare(strict_types=1);

namespace Src\Attribute\Domain\Entities;

use Src\Attribute\Domain\ValueObjects\AttributeId;
use Src\Attribute\Domain\ValueObjects\AttributeValueColor;
use Src\Attribute\Domain\ValueObjects\AttributeValueId;
use Src\Attribute\Domain\ValueObjects\AttributeValueImage;
use Src\Attribute\Domain\ValueObjects\AttributeValueText;

final class ProductAttributeValue
{
    public function __construct(
        private ?AttributeValueId $id,
        private AttributeId $attributeId,
        private AttributeValueText $value,
        private AttributeValueColor $color,
        private AttributeValueImage $image,
        private int $position = 0,
        private ?string $createdAt = null,
        private ?string $updatedAt = null
    ) {}

    public static function create(
        AttributeId $attributeId,
        AttributeValueText $value,
        ?AttributeValueColor $color = null,
        ?AttributeValueImage $image = null,
        int $position = 0
    ): self {
        return new self(
            id: null,
            attributeId: $attributeId,
            value: $value,
            color: $color ?? AttributeValueColor::fromNullableString(null),
            image: $image ?? AttributeValueImage::fromNullableString(null),
            position: $position
        );
    }

    public function id(): ?AttributeValueId
    {
        return $this->id;
    }

    public function attributeId(): AttributeId
    {
        return $this->attributeId;
    }

    public function value(): AttributeValueText
    {
        return $this->value;
    }

    public function color(): AttributeValueColor
    {
        return $this->color;
    }

    public function image(): AttributeValueImage
    {
        return $this->image;
    }

    public function position(): int
    {
        return $this->position;
    }

    public function createdAt(): ?string
    {
        return $this->createdAt;
    }

    public function updatedAt(): ?string
    {
        return $this->updatedAt;
    }

    public function changeValue(AttributeValueText $value): void
    {
        $this->value = $value;
    }

    public function changeColor(AttributeValueColor $color): void
    {
        $this->color = $color;
    }

    public function changeImage(AttributeValueImage $image): void
    {
        $this->image = $image;
    }

    public function changePosition(int $position): void
    {
        $this->position = $position;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id?->value(),
            'product_attribute_id' => $this->attributeId->value(),
            'value' => $this->value->value(),
            'color' => $this->color->value(),
            'image' => $this->image->value(),
            'position' => $this->position,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }
}
