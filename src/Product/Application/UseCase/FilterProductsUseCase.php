<?php

declare(strict_types=1);

namespace Src\Product\Application\UseCase;

use Src\Product\Application\Contracts\ProductRepositoryInterface;
use Src\Product\Application\DTOs\PaginatedProductsResult;
use Src\Product\Application\DTOs\ProductFilterCriteria;

final class FilterProductsUseCase
{
    public function __construct(
        private readonly ProductRepositoryInterface $repository
    ) {}

    public function execute(ProductFilterCriteria $criteria): PaginatedProductsResult
    {
        return $this->repository->filter($criteria);
    }
}
