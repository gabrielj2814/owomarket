<?php

declare(strict_types=1);

namespace Src\Order\Application\UseCases;

use Src\Monetization\Application\UseCases\ReverseOrderCommissionUseCase;
use Src\Order\Application\Contracts\Repositories\OrderRepositoryInterface;
use Src\Order\Domain\Entities\Order;
use Src\Order\Domain\Exceptions\OrderNotFoundException;
use Src\Order\Domain\ValueObjects\OrderId;

final class RefundOrderUseCase
{
    public function __construct(
        private readonly OrderRepositoryInterface $repository,
        private readonly ReverseOrderCommissionUseCase $reverseCommission
    ) {}

    public function execute(string $id): Order
    {
        $order = $this->repository->findById(new OrderId($id));

        if (! $order) {
            throw OrderNotFoundException::withId($id);
        }

        $order->refund();
        $this->repository->save($order);

        // Hallazgo D2: si se devuelve el dinero al cliente, la plataforma no
        // puede quedarse con la comisión de esa venta.
        $this->reverseCommission->execute(
            $id,
            ReverseOrderCommissionUseCase::REASON_REFUNDED
        );

        return $order;
    }
}
