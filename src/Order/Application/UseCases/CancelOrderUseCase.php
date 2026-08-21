<?php

declare(strict_types=1);

namespace Src\Order\Application\UseCases;

use Src\Monetization\Application\UseCases\ReverseOrderCommissionUseCase;
use Src\Order\Application\Contracts\Repositories\OrderRepositoryInterface;
use Src\Order\Domain\Entities\Order;
use Src\Order\Domain\Exceptions\OrderNotFoundException;
use Src\Order\Domain\ValueObjects\OrderId;

final class CancelOrderUseCase
{
    public function __construct(
        private readonly OrderRepositoryInterface $repository,
        private readonly ReverseOrderCommissionUseCase $reverseCommission
    ) {}

    public function execute(string $id, ?string $reason = null): Order
    {
        $order = $this->repository->findById(new OrderId($id));

        if (! $order) {
            throw OrderNotFoundException::withId($id);
        }

        $order->cancel($reason);
        $this->repository->save($order);

        // Hallazgo D2: cancelar el pedido tiene que anular también la comisión
        // que la plataforma le cobra a la tienda. Antes esto no ocurría y la
        // comisión de una venta que nunca se cobró entraba igual en la
        // siguiente liquidación.
        $this->reverseCommission->execute(
            $id,
            ReverseOrderCommissionUseCase::REASON_CANCELLED,
            $reason
        );

        return $order;
    }
}
