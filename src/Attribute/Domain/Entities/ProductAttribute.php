<?php

declare(strict_types=1);

namespace Src\Attribute\Domain\Entities;

use Src\Attribute\Domain\ValueObjects\AttributeId;
use Src\Attribute\Domain\ValueObjects\AttributeName;
use Src\Attribute\Domain\ValueObjects\AttributeSlug;
use Src\Attribute\Domain\ValueObjects\AttributeType;

final class ProductAttribute
{
    /**
     * @param  ProductAttributeValue[]  $values
     */
    public function __construct(
        private ?AttributeId $id,
        private AttributeName $name,
        private AttributeSlug $slug,
        private AttributeType $type,
        private bool $isFilterable = false,
        private bool $isVisible = true,
        private int $position = 0,
        private array $values = [],
        private ?string $createdAt = null,
        private ?string $updatedAt = null
    ) {}

    public static function create(
        AttributeName $name,
        AttributeSlug $slug,
        ?AttributeType $type = null,
        bool $isFilterable = false,
        bool $isVisible = true,
        int $position = 0,
        array $values = []
    ): self {
        return new self(
            id: null,
            name: $name,
            slug: $slug,
            type: $type ?? AttributeType::select(),
            isFilterable: $isFilterable,
            isVisible: $isVisible,
            position: $position,
            values: $values
        );
    }

    public function id(): ?AttributeId
    {
        return $this->id;
    }

    public function name(): AttributeName
    {
        return $this->name;
    }

    public function slug(): AttributeSlug
    {
        return $this->slug;
    }

    public function type(): AttributeType
    {
        return $this->type;
    }

    public function isFilterable(): bool
    {
        return $this->isFilterable;
    }

    public function isVisible(): bool
    {
        return $this->isVisible;
    }

    public function position(): int
    {
        return $this->position;
    }

    /**
     * @return ProductAttributeValue[]
     */
    public function values(): array
    {
        return $this->values;
    }

    public function createdAt(): ?string
    {
        return $this->createdAt;
    }

    public function updatedAt(): ?string
    {
        return $this->updatedAt;
    }

    public function changeName(AttributeName $name): void
    {
        $this->name = $name;
    }

    public function changeSlug(AttributeSlug $slug): void
    {
        $this->slug = $slug;
    }

    public function changeType(AttributeType $type): void
    {
        $this->type = $type;
    }

    public function changeIsFilterable(bool $isFilterable): void
    {
        $this->isFilterable = $isFilterable;
    }

    public function changeIsVisible(bool $isVisible): void
    {
        $this->isVisible = $isVisible;
    }

    public function changePosition(int $position): void
    {
        $this->position = $position;
    }

    /**
     * @param  ProductAttributeValue[]  $values
     */
    public function setValues(array $values): void
    {
        $this->values = $values;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id?->value(),
            'name' => $this->name->value(),
            'slug' => $this->slug->value(),
            'type' => $this->type->value(),
            'is_filterable' => $this->isFilterable,
            'is_visible' => $this->isVisible,
            'position' => $this->position,
            'values' => array_map(fn (ProductAttributeValue $val) => $val->toArray(), $this->values),
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }
}
