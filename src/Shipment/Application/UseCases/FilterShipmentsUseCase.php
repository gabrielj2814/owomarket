<?php

declare(strict_types=1);

namespace Src\Shipment\Application\UseCases;

use Src\Shipment\Application\DTOs\FilterShipmentsCriteria;
use Src\Shipment\Application\DTOs\PaginatedShipmentResult;
use Src\Shipment\Application\Repositories\ShipmentRepositoryInterface;

final class FilterShipmentsUseCase
{
    public function __construct(
        private readonly ShipmentRepositoryInterface $repository
    ) {}

    public function execute(FilterShipmentsCriteria $criteria): PaginatedShipmentResult
    {
        return $this->repository->filter($criteria);
    }
}
