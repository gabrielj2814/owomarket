<?php

declare(strict_types=1);

namespace Src\Category\Application\UseCase;

use Src\Category\Application\Contracts\CategoryRepositoryInterface;
use Src\Category\Domain\Entities\Category;
use Src\Category\Domain\Exceptions\CategoryNotFoundException;
use Src\Category\Domain\ValueObjects\CategoryId;

class ConsultCategoryByIdUseCase
{
    public function __construct(
        protected CategoryRepositoryInterface $categoryRepository
    ) {}

    public function execute(int $id): Category
    {
        $categoryId = CategoryId::fromInt($id);
        $category = $this->categoryRepository->findById($categoryId);

        if ($category === null) {
            throw CategoryNotFoundException::withId($id);
        }

        return $category;
    }
}
