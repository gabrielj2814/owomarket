<?php

declare(strict_types=1);

namespace Src\Order\Application\UseCases;

use Src\Order\Application\Contracts\Repositories\OrderRepositoryInterface;
use Src\Order\Domain\Entities\Order;
use Src\Order\Domain\Exceptions\OrderNotFoundException;
use Src\Order\Domain\ValueObjects\OrderId;

final class CancelOrderUseCase
{
    public function __construct(
        private readonly OrderRepositoryInterface $repository
    ) {}

    public function execute(string $id, ?string $reason = null): Order
    {
        $order = $this->repository->findById(new OrderId($id));

        if (! $order) {
            throw OrderNotFoundException::withId($id);
        }

        $order->cancel($reason);
        $this->repository->save($order);

        return $order;
    }
}
