<?php

declare(strict_types=1);

namespace Src\Category\Application\Contracts;

use Src\Category\Application\DTOs\CategoryFilterCriteria;
use Src\Category\Application\DTOs\PaginatedCategoriesResult;
use Src\Category\Domain\Entities\Category;
use Src\Category\Domain\ValueObjects\CategoryId;
use Src\Category\Domain\ValueObjects\CategorySlug;

interface CategoryRepositoryInterface
{
    public function create(Category $category): Category;

    public function edit(Category $category): Category;

    public function findById(CategoryId $id): ?Category;

    public function findBySlug(CategorySlug $slug): ?Category;

    public function delete(CategoryId $id): void;

    public function filter(CategoryFilterCriteria $criteria): PaginatedCategoriesResult;

    /**
     * @return Category[]
     */
    public function getTree(): array;
}
