<?php

declare(strict_types=1);

namespace Src\Shipment\Application\UseCases;

use Src\Shipment\Application\Repositories\ShipmentRepositoryInterface;
use Src\Shipment\Domain\Entities\Shipment;

final class ConsultShipmentByOrderIdUseCase
{
    public function __construct(
        private readonly ShipmentRepositoryInterface $repository
    ) {}

    /**
     * @return Shipment[]
     */
    public function execute(string $orderId): array
    {
        return $this->repository->findByOrderId($orderId);
    }
}
