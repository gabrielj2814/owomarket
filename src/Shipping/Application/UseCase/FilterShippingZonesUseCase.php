<?php

declare(strict_types=1);

namespace Src\Shipping\Application\UseCase;

use Src\Shipping\Application\Contracts\ShippingRepositoryInterface;
use Src\Shipping\Application\DTOs\PaginatedShippingZonesResult;
use Src\Shipping\Application\DTOs\ShippingZoneFilterCriteria;

final class FilterShippingZonesUseCase
{
    public function __construct(
        private readonly ShippingRepositoryInterface $repository
    ) {}

    public function execute(ShippingZoneFilterCriteria $criteria): PaginatedShippingZonesResult
    {
        return $this->repository->filterZones($criteria);
    }
}
