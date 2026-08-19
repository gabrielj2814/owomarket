<?php

declare(strict_types=1);

namespace Src\Review\Application\UseCases;

use Src\Review\Application\DTOs\FilterReviewsCriteria;
use Src\Review\Application\DTOs\PaginatedReviewResult;
use Src\Review\Application\Repositories\ReviewRepositoryInterface;

final class FilterReviewsUseCase
{
    public function __construct(
        private readonly ReviewRepositoryInterface $repository
    ) {}

    public function execute(FilterReviewsCriteria $criteria): PaginatedReviewResult
    {
        return $this->repository->filter($criteria);
    }
}
