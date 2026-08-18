<?php

declare(strict_types=1);

use Src\Order\Domain\Entities\Order;
use Src\Order\Domain\Entities\OrderItem;
use Src\Order\Domain\Exceptions\EmptyOrderItemsException;
use Src\Order\Domain\Exceptions\InvalidOrderStateTransitionException;
use Src\Order\Domain\ValueObjects\OrderStatus;
use Src\Order\Domain\ValueObjects\PaymentStatus;

it('creates an Order aggregate and calculates subtotal, tax, shipping, discount and total', function () {
    $item1 = OrderItem::create(
        productId: 'prod-1',
        productName: 'Teclado Mecánico',
        sku: 'KEY-01',
        price: 50.00,
        quantity: 2
    ); // subtotal = 100.00

    $item2 = OrderItem::create(
        productId: 'prod-2',
        productName: 'Mouse Gamer',
        sku: 'MOU-01',
        price: 30.00,
        quantity: 1
    ); // subtotal = 30.00

    $order = Order::create(
        customerId: 'cust-uuid-123',
        paymentMethod: 'credit_card',
        items: [$item1, $item2],
        taxAmount: 24.70, // 19% de 130
        shippingAmount: 10.00,
        discountAmount: 15.00,
        currency: 'USD'
    );

    expect($order->customerId())->toBe('cust-uuid-123')
        ->and($order->paymentMethod())->toBe('credit_card')
        ->and($order->status())->toBe(OrderStatus::PENDING)
        ->and($order->paymentStatus())->toBe(PaymentStatus::PENDING)
        ->and($order->subtotal()->amount())->toBe(130.00)
        ->and($order->taxAmount()->amount())->toBe(24.70)
        ->and($order->shippingAmount()->amount())->toBe(10.00)
        ->and($order->discountAmount()->amount())->toBe(15.00)
        ->and($order->total()->amount())->toBe(149.70) // 130 + 24.70 + 10 - 15 = 149.70
        ->and($order->items())->toHaveCount(2)
        ->and($order->items()[0]->orderId()?->value())->toBe($order->id()->value());

    $array = $order->toArray();
    expect($array['customer_id'])->toBe('cust-uuid-123')
        ->and($array['total'])->toBe(149.70)
        ->and($array['status'])->toBe('pending')
        ->and($array['items'])->toHaveCount(2);
});

it('throws EmptyOrderItemsException when creating Order without items', function () {
    Order::create(
        customerId: 'cust-uuid-123',
        paymentMethod: 'transfer',
        items: []
    );
})->throws(EmptyOrderItemsException::class);

it('tests valid full lifecycle transitions for Order', function () {
    $item = OrderItem::create(
        productId: 'prod-1',
        productName: 'Monitor 4K',
        sku: 'MON-4K',
        price: 300.00,
        quantity: 1
    );

    $order = Order::create(
        customerId: 'cust-uuid-123',
        paymentMethod: 'paypal',
        items: [$item]
    );

    expect($order->status())->toBe(OrderStatus::PENDING);

    // 1. Confirm
    $order->confirm();
    expect($order->status())->toBe(OrderStatus::CONFIRMED)
        ->and($order->confirmedAt())->not->toBeNull();

    // 2. Process
    $order->process();
    expect($order->status())->toBe(OrderStatus::PROCESSING);

    // 3. Mark payment as paid
    $order->markPaymentPaid();
    expect($order->paymentStatus())->toBe(PaymentStatus::PAID);

    // 4. Ship
    $order->markAsShipped('Chilexpress Express');
    expect($order->status())->toBe(OrderStatus::SHIPPED)
        ->and($order->shippingMethod())->toBe('Chilexpress Express')
        ->and($order->shippedAt())->not->toBeNull();

    // 5. Deliver
    $order->markAsDelivered();
    expect($order->status())->toBe(OrderStatus::DELIVERED)
        ->and($order->deliveredAt())->not->toBeNull();

    // 6. Refund
    $order->refund();
    expect($order->status())->toBe(OrderStatus::REFUNDED)
        ->and($order->paymentStatus())->toBe(PaymentStatus::REFUNDED);
});

it('tests cancelling a pending order', function () {
    $item = OrderItem::create(
        productId: 'prod-1',
        productName: 'Teclado',
        sku: 'KEY-1',
        price: 20.0,
        quantity: 1
    );

    $order = Order::create(
        customerId: 'cust-1',
        paymentMethod: 'cash',
        items: [$item]
    );

    $order->cancel('Cliente solicitó anulación por error de compra');

    expect($order->status())->toBe(OrderStatus::CANCELLED)
        ->and($order->cancelledAt())->not->toBeNull()
        ->and($order->notes())->toContain('Cliente solicitó anulación');
});

it('throws InvalidOrderStateTransitionException on invalid state transition', function () {
    $item = OrderItem::create(
        productId: 'prod-1',
        productName: 'Teclado',
        sku: 'KEY-1',
        price: 20.0,
        quantity: 1
    );

    $order = Order::create(
        customerId: 'cust-1',
        paymentMethod: 'cash',
        items: [$item]
    );

    // Cannot ship directly from pending
    $order->markAsShipped();
})->throws(InvalidOrderStateTransitionException::class);
