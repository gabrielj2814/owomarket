<?php

declare(strict_types=1);

namespace Src\Category\Domain\Entities;

use Src\Category\Domain\ValueObjects\CategoryDescription;
use Src\Category\Domain\ValueObjects\CategoryId;
use Src\Category\Domain\ValueObjects\CategoryName;
use Src\Category\Domain\ValueObjects\CategorySlug;
use Src\Category\Domain\ValueObjects\CategoryStatus;
use Src\Category\Domain\ValueObjects\ParentCategoryId;

class Category
{
    /**
     * @param  Category[]  $children
     */
    private function __construct(
        private CategoryId $id,
        private CategoryName $name,
        private CategorySlug $slug,
        private CategoryDescription $description,
        private ?string $image,
        private ParentCategoryId $parentId,
        private CategoryStatus $isActive,
        private int $position = 0,
        private ?string $createdAt = null,
        private ?string $updatedAt = null,
        private array $children = []
    ) {}

    public static function create(
        CategoryName $name,
        CategorySlug $slug,
        CategoryDescription $description,
        ?string $image = null,
        ?ParentCategoryId $parentId = null,
        ?CategoryStatus $isActive = null,
        int $position = 0
    ): self {
        return new self(
            CategoryId::null(),
            $name,
            $slug,
            $description,
            $image,
            $parentId ?? ParentCategoryId::null(),
            $isActive ?? CategoryStatus::active(),
            $position
        );
    }

    /**
     * @param  Category[]  $children
     */
    public static function reconstitute(
        CategoryId $id,
        CategoryName $name,
        CategorySlug $slug,
        CategoryDescription $description,
        ?string $image = null,
        ?ParentCategoryId $parentId = null,
        ?CategoryStatus $isActive = null,
        int $position = 0,
        ?string $createdAt = null,
        ?string $updatedAt = null,
        array $children = []
    ): self {
        return new self(
            $id,
            $name,
            $slug,
            $description,
            $image,
            $parentId ?? ParentCategoryId::null(),
            $isActive ?? CategoryStatus::active(),
            $position,
            $createdAt,
            $updatedAt,
            $children
        );
    }

    public function getId(): CategoryId
    {
        return $this->id;
    }

    public function getName(): CategoryName
    {
        return $this->name;
    }

    public function getSlug(): CategorySlug
    {
        return $this->slug;
    }

    public function getDescription(): CategoryDescription
    {
        return $this->description;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function getParentId(): ParentCategoryId
    {
        return $this->parentId;
    }

    public function getIsActive(): CategoryStatus
    {
        return $this->isActive;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function getCreatedAt(): ?string
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?string
    {
        return $this->updatedAt;
    }

    /**
     * @return Category[]
     */
    public function getChildren(): array
    {
        return $this->children;
    }

    public function rename(CategoryName $newName, CategorySlug $newSlug): void
    {
        $this->name = $newName;
        $this->slug = $newSlug;
    }

    public function updateDetails(
        CategoryName $name,
        CategorySlug $slug,
        CategoryDescription $description,
        ?string $image,
        ParentCategoryId $parentId,
        CategoryStatus $isActive,
        int $position
    ): void {
        $this->name = $name;
        $this->slug = $slug;
        $this->description = $description;
        $this->image = $image;
        $this->parentId = $parentId;
        $this->isActive = $isActive;
        $this->position = $position;
    }

    public function activate(): void
    {
        $this->isActive = CategoryStatus::active();
    }

    public function deactivate(): void
    {
        $this->isActive = CategoryStatus::inactive();
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id->value(),
            'name' => $this->name->value(),
            'slug' => $this->slug->value(),
            'description' => $this->description->value(),
            'image' => $this->image,
            'parent_id' => $this->parentId->value(),
            'is_active' => $this->isActive->value(),
            'position' => $this->position,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
            'children' => array_map(fn (Category $child) => $child->toArray(), $this->children),
        ];
    }
}
