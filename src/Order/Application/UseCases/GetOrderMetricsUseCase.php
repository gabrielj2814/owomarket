<?php

declare(strict_types=1);

namespace Src\Order\Application\UseCases;

use Src\Order\Application\Contracts\Repositories\OrderRepositoryInterface;
use Src\Order\Application\DTOs\OrderMetricsData;

final class GetOrderMetricsUseCase
{
    public function __construct(
        private readonly OrderRepositoryInterface $repository
    ) {}

    public function execute(): OrderMetricsData
    {
        return $this->repository->getMetrics();
    }
}
