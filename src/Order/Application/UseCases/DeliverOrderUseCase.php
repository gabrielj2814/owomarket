<?php

declare(strict_types=1);

namespace Src\Order\Application\UseCases;

use Src\Monetization\Application\UseCases\DeclareOrderDeliveredUseCase;
use Src\Order\Application\Contracts\Repositories\OrderRepositoryInterface;
use Src\Order\Domain\Entities\Order;
use Src\Order\Domain\Exceptions\OrderNotFoundException;
use Src\Order\Domain\ValueObjects\OrderId;

final class DeliverOrderUseCase
{
    public function __construct(
        private readonly OrderRepositoryInterface $repository,
        private readonly DeclareOrderDeliveredUseCase $declareDelivered
    ) {}

    public function execute(string $id): Order
    {
        $order = $this->repository->findById(new OrderId($id));

        if (! $order) {
            throw OrderNotFoundException::withId($id);
        }

        $order->markAsDelivered();
        $this->repository->save($order);

        // Subsistema 3: esto ANTES liberaba el dinero directamente. Y esta ruta la expone
        // `routes/tenantApi.php`, o sea que el comerciante declaraba su propia entrega y con
        // ello hacia retirable su propio importe. Ahora solo arranca el reloj: libera el
        // comprador al confirmar, o el plazo al vencer.
        //
        // Sigue yendo DESPUES del guardado y fuera de cualquier transaccion del inquilino,
        // que es la leccion de N25: escribe en la base central y acoplar la entrega a esa
        // escritura es como se pierde una entrega por un fallo de red.
        $this->declareDelivered->execute($id);

        return $order;
    }
}
