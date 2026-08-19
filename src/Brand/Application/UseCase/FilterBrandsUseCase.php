<?php

declare(strict_types=1);

namespace Src\Brand\Application\UseCase;

use Src\Brand\Application\Contracts\BrandRepositoryInterface;
use Src\Brand\Application\DTOs\BrandFilterCriteria;
use Src\Brand\Application\DTOs\PaginatedBrandsResult;

final class FilterBrandsUseCase
{
    public function __construct(
        private readonly BrandRepositoryInterface $repository
    ) {}

    public function execute(BrandFilterCriteria $criteria): PaginatedBrandsResult
    {
        return $this->repository->filter($criteria);
    }
}
