<?php

declare(strict_types=1);

namespace Src\Shipment\Application\UseCases;

use Src\Shipment\Application\DTOs\ShipmentMetricsData;
use Src\Shipment\Application\Repositories\ShipmentRepositoryInterface;

final class GetShipmentMetricsUseCase
{
    public function __construct(
        private readonly ShipmentRepositoryInterface $repository
    ) {}

    public function execute(): ShipmentMetricsData
    {
        return $this->repository->getMetrics();
    }
}
