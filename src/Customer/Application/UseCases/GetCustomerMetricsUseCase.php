<?php

declare(strict_types=1);

namespace Src\Customer\Application\UseCases;

use Src\Customer\Application\Contracts\Repositories\CustomerRepositoryInterface;
use Src\Customer\Application\DTOs\CustomerMetricsData;

final class GetCustomerMetricsUseCase
{
    public function __construct(
        private readonly CustomerRepositoryInterface $customerRepository
    ) {}

    public function execute(): CustomerMetricsData
    {
        return $this->customerRepository->getMetrics();
    }
}
