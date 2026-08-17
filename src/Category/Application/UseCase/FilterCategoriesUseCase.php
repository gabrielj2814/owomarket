<?php

declare(strict_types=1);

namespace Src\Category\Application\UseCase;

use Src\Category\Application\Contracts\CategoryRepositoryInterface;
use Src\Category\Application\DTOs\CategoryFilterCriteria;
use Src\Category\Application\DTOs\PaginatedCategoriesResult;

class FilterCategoriesUseCase
{
    public function __construct(
        protected CategoryRepositoryInterface $categoryRepository
    ) {}

    public function execute(CategoryFilterCriteria $criteria): PaginatedCategoriesResult
    {
        return $this->categoryRepository->filter($criteria);
    }
}
