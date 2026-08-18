<?php

declare(strict_types=1);

namespace Src\Order\Application\UseCases;

use Src\Order\Application\Contracts\Repositories\OrderRepositoryInterface;
use Src\Order\Application\DTOs\FilterOrdersCriteria;
use Src\Order\Application\DTOs\PaginatedOrderResult;

final class FilterOrdersUseCase
{
    public function __construct(
        private readonly OrderRepositoryInterface $repository
    ) {}

    public function execute(FilterOrdersCriteria $criteria): PaginatedOrderResult
    {
        return $this->repository->filter($criteria);
    }
}
