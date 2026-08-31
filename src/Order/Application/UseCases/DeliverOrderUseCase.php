<?php

declare(strict_types=1);

namespace Src\Order\Application\UseCases;

use Src\Monetization\Application\UseCases\ReleaseOrderCommissionUseCase;
use Src\Order\Application\Contracts\Repositories\OrderRepositoryInterface;
use Src\Order\Domain\Entities\Order;
use Src\Order\Domain\Exceptions\OrderNotFoundException;
use Src\Order\Domain\ValueObjects\OrderId;

final class DeliverOrderUseCase
{
    public function __construct(
        private readonly OrderRepositoryInterface $repository,
        private readonly ReleaseOrderCommissionUseCase $releaseCommission
    ) {}

    public function execute(string $id): Order
    {
        $order = $this->repository->findById(new OrderId($id));

        if (! $order) {
            throw OrderNotFoundException::withId($id);
        }

        $order->markAsDelivered();
        $this->repository->save($order);

        // Fase 4b: la mercancia llego, asi que su comision deja de estar retenida y pasa a
        // ser retirable. Va DESPUES del guardado y fuera de cualquier transaccion del
        // inquilino: escribe en la base central, y acoplar la entrega a esa escritura es la
        // leccion de N25.
        $this->releaseCommission->execute($id);

        return $order;
    }
}
