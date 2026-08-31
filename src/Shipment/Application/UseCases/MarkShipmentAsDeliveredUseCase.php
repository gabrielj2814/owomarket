<?php

declare(strict_types=1);

namespace Src\Shipment\Application\UseCases;

use DateTimeImmutable;
use Src\Monetization\Application\UseCases\ReleaseOrderCommissionUseCase;
use Src\Order\Application\Contracts\Repositories\OrderRepositoryInterface;
use Src\Order\Domain\ValueObjects\OrderId;
use Src\Shipment\Application\Repositories\ShipmentRepositoryInterface;
use Src\Shipment\Domain\Entities\Shipment;
use Src\Shipment\Domain\Exceptions\ShipmentNotFoundException;
use Src\Shipment\Domain\ValueObjects\ShipmentId;

final class MarkShipmentAsDeliveredUseCase
{
    public function __construct(
        private readonly ShipmentRepositoryInterface $repository,
        private readonly OrderRepositoryInterface $orders,
        private readonly ReleaseOrderCommissionUseCase $releaseCommission
    ) {}

    public function execute(string $shipmentId, ?string $deliveredAt = null): Shipment
    {
        $id = ShipmentId::fromString($shipmentId);
        $shipment = $this->repository->findById($id);

        if ($shipment === null) {
            throw ShipmentNotFoundException::forId($shipmentId);
        }

        $deliveredAtDt = ! empty($deliveredAt) ? new DateTimeImmutable($deliveredAt) : null;
        $shipment->markAsDelivered($deliveredAtDt);

        $guardado = $this->repository->save($shipment);

        // Fase 4b: entregar el envio puede llevar el pedido a `delivered` --lo decide la
        // guarda del dominio en el repositorio, hallazgo SH1-- y solo entonces se libera la
        // comision. Se pregunta por el estado real en vez de darlo por hecho: un pedido que
        // ya estaba entregado por otro envio no vuelve a liberarse, y uno que la guarda no
        // dejo avanzar no se libera.
        $order = $this->orders->findById(new OrderId($shipment->orderId()));

        if ($order?->status()->isDelivered()) {
            $this->releaseCommission->execute($shipment->orderId());
        }

        return $guardado;
    }
}
