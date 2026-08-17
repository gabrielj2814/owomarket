<?php

declare(strict_types=1);

namespace Src\Category\Application\UseCase;

use InvalidArgumentException;
use Src\Category\Application\Contracts\CategoryRepositoryInterface;
use Src\Category\Domain\Entities\Category;
use Src\Category\Domain\Exceptions\CategoryNotFoundException;
use Src\Category\Domain\ValueObjects\CategoryDescription;
use Src\Category\Domain\ValueObjects\CategoryId;
use Src\Category\Domain\ValueObjects\CategoryName;
use Src\Category\Domain\ValueObjects\CategorySlug;
use Src\Category\Domain\ValueObjects\CategoryStatus;
use Src\Category\Domain\ValueObjects\ParentCategoryId;

class EditCategoryUseCase
{
    public function __construct(
        protected CategoryRepositoryInterface $categoryRepository
    ) {}

    public function execute(
        int $id,
        string $name,
        ?string $slug = null,
        ?string $description = null,
        ?string $image = null,
        ?int $parentId = null,
        bool $isActive = true,
        int $position = 0
    ): Category {
        $categoryId = CategoryId::fromInt($id);
        $category = $this->categoryRepository->findById($categoryId);

        if ($category === null) {
            throw CategoryNotFoundException::withId($id);
        }

        $categoryName = CategoryName::make($name);
        $categorySlug = $slug !== null && trim($slug) !== ''
            ? CategorySlug::fromString($slug)
            : CategorySlug::fromString($name);

        $existingWithSlug = $this->categoryRepository->findBySlug($categorySlug);
        if ($existingWithSlug !== null && $existingWithSlug->getId()->value() !== $id) {
            throw new InvalidArgumentException("Category with slug '{$categorySlug->value()}' already exists.");
        }

        if ($parentId !== null && $parentId === $id) {
            throw new InvalidArgumentException('A category cannot be its own parent.');
        }

        $categoryDescription = CategoryDescription::make($description);
        $parentCategoryId = ParentCategoryId::fromNullableInt($parentId);
        $categoryStatus = CategoryStatus::fromBool($isActive);

        $category->updateDetails(
            name: $categoryName,
            slug: $categorySlug,
            description: $categoryDescription,
            image: $image,
            parentId: $parentCategoryId,
            isActive: $categoryStatus,
            position: $position
        );

        return $this->categoryRepository->edit($category);
    }
}
