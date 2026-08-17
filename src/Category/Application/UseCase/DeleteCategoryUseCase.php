<?php

declare(strict_types=1);

namespace Src\Category\Application\UseCase;

use Src\Category\Application\Contracts\CategoryRepositoryInterface;
use Src\Category\Domain\Exceptions\CategoryNotFoundException;
use Src\Category\Domain\ValueObjects\CategoryId;

class DeleteCategoryUseCase
{
    public function __construct(
        protected CategoryRepositoryInterface $categoryRepository
    ) {}

    public function execute(int $id): void
    {
        $categoryId = CategoryId::fromInt($id);
        $category = $this->categoryRepository->findById($categoryId);

        if ($category === null) {
            throw CategoryNotFoundException::withId($id);
        }

        $this->categoryRepository->delete($categoryId);
    }
}
