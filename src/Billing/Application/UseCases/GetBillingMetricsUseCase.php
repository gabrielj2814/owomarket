<?php

declare(strict_types=1);

namespace Src\Billing\Application\UseCases;

use Src\Billing\Application\Contracts\Repositories\InvoiceRepositoryInterface;

final class GetBillingMetricsUseCase
{
    public function __construct(
        private readonly InvoiceRepositoryInterface $repository
    ) {}

    /**
     * @return array{total_billed: float, total_issued: int, total_paid: int, total_cancelled: int}
     */
    public function execute(): array
    {
        return $this->repository->getMetrics();
    }
}
