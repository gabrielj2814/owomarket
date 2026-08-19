<?php

declare(strict_types=1);

namespace Src\Billing\Application\UseCases;

use Src\Billing\Application\Contracts\Repositories\InvoiceRepositoryInterface;
use Src\Billing\Application\DTOs\FilterInvoicesCriteria;
use Src\Billing\Application\DTOs\PaginatedInvoicesResult;

final class FilterInvoicesUseCase
{
    public function __construct(
        private readonly InvoiceRepositoryInterface $repository
    ) {}

    public function execute(FilterInvoicesCriteria $criteria): PaginatedInvoicesResult
    {
        return $this->repository->filter($criteria);
    }
}
