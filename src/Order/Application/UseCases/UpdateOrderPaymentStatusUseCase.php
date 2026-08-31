<?php

declare(strict_types=1);

namespace Src\Order\Application\UseCases;

use InvalidArgumentException;
use Src\Order\Application\Contracts\Repositories\OrderRepositoryInterface;
use Src\Order\Domain\Entities\Order;
use Src\Order\Domain\Exceptions\InvalidOrderStateTransitionException;
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
            // Fase 3b: el comerciante ya no puede declarar que un pago llego. Desde que la
            // plataforma cobra todas las ventas, el dinero entra en SU cuenta y el comerciante
            // no ve ese extracto: marcarlo aqui era autocertificarse un cobro para desbloquear
            // un retiro contra una cuenta ajena.
            //
            // Se rechaza con el motivo en vez de sacarlo del `in:` del FormRequest, que daria
            // un 422 generico. Lo que el comerciante necesita saber es que existe otro camino.
            PaymentStatus::PAID => throw new InvalidArgumentException(
                'El cobro lo confirma la plataforma, que es donde entra el dinero. '
                .'Si el comprador te pasó una referencia por otro canal, repórtala en el pedido.'
            ),
            PaymentStatus::FAILED => $order->markPaymentFailed(),
            PaymentStatus::REFUNDED => $order->refund(),
            // Hallazgo OR1: antes era `null`. El endpoint respondia 200 con "actualizado a
            // 'pending' exitosamente" y el pedido se quedaba igual. Revertir de verdad
            // implicaria deshacer la comision ya activada -- otro modulo, otra decision --,
            // asi que de momento se rechaza con el motivo en vez de fingir exito.
            PaymentStatus::PENDING => throw InvalidOrderStateTransitionException::payment(
                $order->paymentStatus()->value,
                PaymentStatus::PENDING->value
            ),
            default => throw new InvalidArgumentException("Estado de pago no soportado: '{$paymentStatus}'."),
        };

        $this->repository->save($order);

        // Aqui se promovia la comision cuando el comerciante marcaba el pedido como pagado
        // (N15). Ya no puede: los dos unicos puntos que confirman un cobro son
        // `ConfirmStorefrontPaymentUseCase` y `ConfirmCentralOrderPaymentUseCase`, los dos del
        // lado de la plataforma.

        return $order;
    }
}
