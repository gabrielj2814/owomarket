<?php

declare(strict_types=1);

namespace Src\Category\Application\UseCase;

use InvalidArgumentException;
use Src\Category\Application\Contracts\CategoryRepositoryInterface;
use Src\Category\Domain\Entities\Category;
use Src\Category\Domain\ValueObjects\CategoryDescription;
use Src\Category\Domain\ValueObjects\CategoryName;
use Src\Category\Domain\ValueObjects\CategorySlug;
use Src\Category\Domain\ValueObjects\CategoryStatus;
use Src\Category\Domain\ValueObjects\ParentCategoryId;

class CreateCategoryUseCase
{
    public function __construct(
        protected CategoryRepositoryInterface $categoryRepository
    ) {}

    public function execute(
        string $name,
        ?string $slug = null,
        ?string $description = null,
        ?string $image = null,
        ?int $parentId = null,
        bool $isActive = true,
        int $position = 0
    ): Category {
        $categoryName = CategoryName::make($name);
        $categorySlug = $slug !== null && trim($slug) !== ''
            ? CategorySlug::fromString($slug)
            : CategorySlug::fromString($name);

        $existing = $this->categoryRepository->findBySlug($categorySlug);
        if ($existing !== null) {
            throw new InvalidArgumentException("Category with slug '{$categorySlug->value()}' already exists.");
        }

        $categoryDescription = CategoryDescription::make($description);
        $parentCategoryId = ParentCategoryId::fromNullableInt($parentId);
        $categoryStatus = CategoryStatus::fromBool($isActive);

        $category = Category::create(
            name: $categoryName,
            slug: $categorySlug,
            description: $categoryDescription,
            image: $image,
            parentId: $parentCategoryId,
            isActive: $categoryStatus,
            position: $position
        );

        return $this->categoryRepository->create($category);
    }
}
