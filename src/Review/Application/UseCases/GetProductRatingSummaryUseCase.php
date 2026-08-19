<?php

declare(strict_types=1);

namespace Src\Review\Application\UseCases;

use Src\Review\Application\DTOs\ProductRatingSummaryData;
use Src\Review\Application\Repositories\ReviewRepositoryInterface;

final class GetProductRatingSummaryUseCase
{
    public function __construct(
        private readonly ReviewRepositoryInterface $repository
    ) {}

    public function execute(?string $productId = null): ProductRatingSummaryData
    {
        return $this->repository->getRatingSummary($productId);
    }
}
