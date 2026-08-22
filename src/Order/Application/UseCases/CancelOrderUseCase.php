<?php

declare(strict_types=1);

namespace Src\Order\Application\UseCases;

use Src\Marketplace\Application\Service\StockReserver;
use Src\Monetization\Application\UseCases\ReverseOrderCommissionUseCase;
use Src\Order\Application\Contracts\Repositories\OrderRepositoryInterface;
use Src\Order\Domain\Entities\Order;
use Src\Order\Domain\Exceptions\OrderNotFoundException;
use Src\Order\Domain\ValueObjects\OrderId;

final class CancelOrderUseCase
{
    public function __construct(
        private readonly OrderRepositoryInterface $repository,
        private readonly ReverseOrderCommissionUseCase $reverseCommission,
        private readonly StockReserver $stockReserver
    ) {}

    public function execute(string $id, ?string $reason = null): Order
    {
        $order = $this->repository->findById(new OrderId($id));

        if (! $order) {
            throw OrderNotFoundException::withId($id);
        }

        $order->cancel($reason);
        $this->repository->save($order);

        // Hallazgo N13: `StockReserver::release()` existia desde la Fase 1.3 y **no lo
        // llamaba nadie**, asi que cancelar un pedido no devolvia una sola unidad al
        // inventario. Quedaba pendiente «decidir en que estados corresponde reponer»,
        // pero esa decision ya la toma el dominio: `OrderStatus::canBeCancelled()` solo
        // admite pending, confirmed y processing — un pedido enviado NO se puede
        // cancelar. Si llegamos aqui, la mercancia nunca salio del almacen.
        //
        // El reembolso es otra historia y por eso no se toca: `canBeRefunded()` incluye
        // shipped y delivered, donde el producto puede estar en casa del cliente.
        foreach ($order->items() as $item) {
            $this->stockReserver->release(
                $item->productId(),
                $item->productVariantId(),
                $item->quantity()
            );
        }

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
