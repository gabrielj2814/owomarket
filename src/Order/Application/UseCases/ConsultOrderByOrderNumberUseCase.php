<?php

declare(strict_types=1);

namespace Src\Order\Application\UseCases;

use Src\Order\Application\Contracts\Repositories\OrderRepositoryInterface;
use Src\Order\Domain\Entities\Order;
use Src\Order\Domain\Exceptions\OrderNotFoundException;
use Src\Order\Domain\ValueObjects\OrderNumber;

final class ConsultOrderByOrderNumberUseCase
{
    public function __construct(
        private readonly OrderRepositoryInterface $repository
    ) {}

    public function execute(string $orderNumber): Order
    {
        $order = $this->repository->findByOrderNumber(new OrderNumber($orderNumber));

        if (! $order) {
            throw OrderNotFoundException::withOrderNumber($orderNumber);
        }

        return $order;
    }
}
