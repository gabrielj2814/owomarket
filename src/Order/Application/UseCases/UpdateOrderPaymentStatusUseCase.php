<?php

declare(strict_types=1);

namespace Src\Order\Application\UseCases;

use InvalidArgumentException;
use Src\Order\Application\Contracts\Repositories\OrderRepositoryInterface;
use Src\Order\Domain\Entities\Order;
use Src\Order\Domain\Exceptions\OrderNotFoundException;
use Src\Order\Domain\ValueObjects\OrderId;
use Src\Order\Domain\ValueObjects\PaymentStatus;

final class UpdateOrderPaymentStatusUseCase
{
    public function __construct(
        private readonly OrderRepositoryInterface $repository
    ) {}

    public function execute(string $id, string $paymentStatus): Order
    {
        $order = $this->repository->findById(new OrderId($id));

        if (! $order) {
            throw OrderNotFoundException::withId($id);
        }

        $status = PaymentStatus::fromString($paymentStatus);

        match ($status) {
            PaymentStatus::PAID => $order->markPaymentPaid(),
            PaymentStatus::FAILED => $order->markPaymentFailed(),
            PaymentStatus::REFUNDED => $order->refund(),
            PaymentStatus::PENDING => null,
            default => throw new InvalidArgumentException("Estado de pago no soportado: '{$paymentStatus}'."),
        };

        $this->repository->save($order);

        return $order;
    }
}
