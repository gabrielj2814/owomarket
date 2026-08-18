<?php

declare(strict_types=1);

namespace Src\Customer\Application\UseCases;

use Src\Customer\Application\Contracts\Repositories\CustomerRepositoryInterface;
use Src\Customer\Application\DTOs\FilterCustomersCriteria;
use Src\Customer\Application\DTOs\PaginatedCustomerResult;

final class FilterCustomersUseCase
{
    public function __construct(
        private readonly CustomerRepositoryInterface $customerRepository
    ) {}

    public function execute(FilterCustomersCriteria $criteria): PaginatedCustomerResult
    {
        return $this->customerRepository->filter($criteria);
    }
}
