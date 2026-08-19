<?php

declare(strict_types=1);

namespace Src\Category\Application\UseCase;

use Src\Category\Application\Contracts\CategoryRepositoryInterface;
use Src\Category\Domain\Entities\Category;

class ListCategoriesTreeUseCase
{
    public function __construct(
        protected CategoryRepositoryInterface $categoryRepository
    ) {}

    /**
     * @return Category[]
     */
    public function execute(): array
    {
        return $this->categoryRepository->getTree();
    }
}
