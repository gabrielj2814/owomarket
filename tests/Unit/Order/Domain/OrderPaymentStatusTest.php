<?php

declare(strict_types=1);

use Src\Order\Domain\Entities\Order;
use Src\Order\Domain\Entities\OrderItem;
use Src\Order\Domain\Exceptions\InvalidOrderStateTransitionException;
use Src\Order\Domain\ValueObjects\PaymentStatus;

/**
 * Hallazgo OR1: el estado del pedido tenía máquina de estados y el del pago no. Ni una
 * guarda: `markPaymentPaid()` y `markPaymentFailed()` asignaban a pelo, y `refund()`
 * escribía `payment_status = refunded` mirando sólo el estado del *pedido*.
 *
 * De ahí salían combinaciones que no significan nada: un pedido reembolsado volviendo a
 * «pagado», o un pago que nunca pasó de `pending` quedando como «reembolsado».
 */
function orderPagado(): Order
{
    $order = Order::create(
        customerId: 'cust-1',
        paymentMethod: 'transfer',
        items: [OrderItem::create('p-1', 'Prod 1', 'SKU-1', 10.0, 1)]
    );

    $order->confirm();
    $order->markPaymentPaid();

    return $order;
}

it('permite el camino real: un pago pendiente pasa a pagado', function () {
    $order = Order::create(
        customerId: 'cust-1',
        paymentMethod: 'cash',
        items: [OrderItem::create('p-1', 'Prod 1', 'SKU-1', 10.0, 1)]
    );

    $order->markPaymentPaid();

    expect($order->paymentStatus())->toBe(PaymentStatus::PAID);
});

it('permite reintentar un pago que había fallado', function () {
    $order = Order::create(
        customerId: 'cust-1',
        paymentMethod: 'card',
        items: [OrderItem::create('p-1', 'Prod 1', 'SKU-1', 10.0, 1)]
    );

    $order->markPaymentFailed();
    expect($order->paymentStatus())->toBe(PaymentStatus::FAILED);

    $order->markPaymentPaid();
    expect($order->paymentStatus())->toBe(PaymentStatus::PAID);
});

it('rechaza volver a marcar como pagado un pedido ya reembolsado', function () {
    $order = orderPagado();
    $order->process();
    $order->refund();

    expect($order->paymentStatus())->toBe(PaymentStatus::REFUNDED);

    $order->markPaymentPaid();
})->throws(InvalidOrderStateTransitionException::class);

it('rechaza marcar como pagado un pago que ya está pagado', function () {
    orderPagado()->markPaymentPaid();
})->throws(InvalidOrderStateTransitionException::class);

it('rechaza marcar como fallido un pago ya cobrado', function () {
    orderPagado()->markPaymentFailed();
})->throws(InvalidOrderStateTransitionException::class);

it('reembolsa un pedido entregado y cobrado', function () {
    $order = orderPagado();
    $order->process();
    $order->markAsShipped();
    $order->markAsDelivered();

    $order->refund();

    expect($order->paymentStatus())->toBe(PaymentStatus::REFUNDED);
});

it('reembolsa un pedido de pago móvil que se cobró en mano y nunca se marcó pagado', function () {
    // N15 lo deja escrito: en pago móvil, transferencia manual y contra entrega el
    // `payment_status` se queda en `pending` para siempre. La primera versión de esta guarda
    // exigía `paid` y tumbó el test de la comisión (hallazgo D2): habría bloqueado el
    // reembolso de los métodos de pago más usados del proyecto.
    $order = Order::create(
        customerId: 'cust-1',
        paymentMethod: 'pago_movil',
        items: [OrderItem::create('p-1', 'Prod 1', 'SKU-1', 10.0, 1)]
    );

    $order->confirm();
    $order->process();
    $order->markAsShipped();
    $order->markAsDelivered();
    expect($order->paymentStatus())->toBe(PaymentStatus::PENDING);

    $order->refund();

    expect($order->paymentStatus())->toBe(PaymentStatus::REFUNDED);
});

it('rechaza reembolsar un pago que falló: ahí nunca entró dinero', function () {
    $order = Order::create(
        customerId: 'cust-1',
        paymentMethod: 'card',
        items: [OrderItem::create('p-1', 'Prod 1', 'SKU-1', 10.0, 1)]
    );

    $order->confirm();
    $order->markPaymentFailed();

    $order->refund();
})->throws(InvalidOrderStateTransitionException::class);

it('rechaza reembolsar dos veces', function () {
    $order = orderPagado();
    $order->process();
    $order->refund();

    // El estado del pedido ya frena la segunda vuelta, pero la guarda del pago la frenaría
    // igual: `refunded` no vuelve a `refunded`.
    expect(PaymentStatus::REFUNDED->canBeRefunded())->toBeFalse();

    $order->refund();
})->throws(InvalidOrderStateTransitionException::class);
