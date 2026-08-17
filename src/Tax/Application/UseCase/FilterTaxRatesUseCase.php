<?php

declare(strict_types=1);

namespace Src\Tax\Application\UseCase;

use Src\Tax\Application\Contracts\TaxRateRepositoryInterface;
use Src\Tax\Application\DTOs\PaginatedTaxRatesResult;
use Src\Tax\Application\DTOs\TaxRateFilterCriteria;

final class FilterTaxRatesUseCase
{
    public function __construct(
        private readonly TaxRateRepositoryInterface $repository
    ) {}

    public function execute(TaxRateFilterCriteria $criteria): PaginatedTaxRatesResult
    {
        return $this->repository->filter($criteria);
    }
}
