<?php

declare(strict_types=1);

use Mockery as m;
use Src\Order\Application\Contracts\Repositories\OrderRepositoryInterface;
use Src\Order\Application\DTOs\CreateOrderData;
use Src\Order\Application\DTOs\FilterOrdersCriteria;
use Src\Order\Application\DTOs\OrderItemInputData;
use Src\Order\Application\DTOs\OrderMetricsData;
use Src\Order\Application\DTOs\PaginatedOrderResult;
use Src\Order\Application\UseCases\CancelOrderUseCase;
use Src\Order\Application\UseCases\ConfirmOrderUseCase;
use Src\Order\Application\UseCases\ConsultOrderByIdUseCase;
use Src\Order\Application\UseCases\ConsultOrderByOrderNumberUseCase;
use Src\Order\Application\UseCases\CreateOrderUseCase;
use Src\Order\Application\UseCases\DeliverOrderUseCase;
use Src\Order\Application\UseCases\FilterOrdersUseCase;
use Src\Order\Application\UseCases\GetOrderMetricsUseCase;
use Src\Order\Application\UseCases\ProcessOrderUseCase;
use Src\Order\Application\UseCases\ShipOrderUseCase;
use Src\Order\Application\UseCases\UpdateOrderPaymentStatusUseCase;
use Src\Order\Domain\Entities\Order;
use Src\Order\Domain\Entities\OrderItem;
use Src\Order\Domain\Exceptions\OrderNotFoundException;
use Src\Order\Domain\ValueObjects\OrderId;
use Src\Order\Domain\ValueObjects\OrderStatus;
use Src\Order\Domain\ValueObjects\PaymentStatus;

afterEach(function () {
    m::close();
});

it('CreateOrderUseCase creates and saves new Order successfully', function () {
    $repository = m::mock(OrderRepositoryInterface::class);
    $repository->shouldReceive('save')->once()->with(m::type(Order::class));

    $useCase = new CreateOrderUseCase($repository);

    $dto = new CreateOrderData(
        customerId: 'cust-123',
        paymentMethod: 'stripe',
        items: [
            new OrderItemInputData(
                productId: 'prod-1',
                productName: 'Producto A',
                sku: 'SKU-A',
                price: 50.0,
                quantity: 2
            ),
        ],
        taxAmount: 19.0,
        shippingAmount: 5.0,
        discountAmount: 10.0,
        currency: 'USD'
    );

    $order = $useCase->execute($dto);

    expect($order->customerId())->toBe('cust-123')
        ->and($order->subtotal()->amount())->toBe(100.0)
        ->and($order->total()->amount())->toBe(114.0) // 100 + 19 + 5 - 10 = 114
        ->and($order->status())->toBe(OrderStatus::PENDING);
});

it('ConsultOrderByIdUseCase returns order or throws exception', function () {
    $repository = m::mock(OrderRepositoryInterface::class);

    $item = OrderItem::create('p-1', 'Prod 1', 'SKU-1', 10.0, 1);
    $order = Order::create('cust-1', 'cash', [$item]);

    $repository->shouldReceive('findById')->once()->with(m::type(OrderId::class))->andReturn($order);

    $useCase = new ConsultOrderByIdUseCase($repository);
    $found = $useCase->execute($order->id()->value());

    expect($found->id()->value())->toBe($order->id()->value());

    $repository->shouldReceive('findById')->once()->andReturn(null);
    $useCase->execute(\Ramsey\Uuid\Uuid::uuid4()->toString());
})->throws(OrderNotFoundException::class);

it('ConsultOrderByOrderNumberUseCase returns order or throws exception', function () {
    $repository = m::mock(OrderRepositoryInterface::class);

    $item = OrderItem::create('p-1', 'Prod 1', 'SKU-1', 10.0, 1);
    $order = Order::create('cust-1', 'cash', [$item]);

    $repository->shouldReceive('findByOrderNumber')->once()->andReturn($order);

    $useCase = new ConsultOrderByOrderNumberUseCase($repository);
    $found = $useCase->execute($order->orderNumber()->value());

    expect($found->orderNumber()->value())->toBe($order->orderNumber()->value());

    $repository->shouldReceive('findByOrderNumber')->once()->andReturn(null);
    $useCase->execute('ORD-NON-EXISTENT');
})->throws(OrderNotFoundException::class);

it('FilterOrdersUseCase returns paginated results', function () {
    $repository = m::mock(OrderRepositoryInterface::class);
    $expected = new PaginatedOrderResult([], 0, 1, 15, 1);

    $repository->shouldReceive('filter')->once()->andReturn($expected);

    $useCase = new FilterOrdersUseCase($repository);
    $criteria = new FilterOrdersCriteria(search: 'John');

    $result = $useCase->execute($criteria);
    expect($result)->toBe($expected);
});

it('ConfirmOrderUseCase, ProcessOrderUseCase, ShipOrderUseCase, DeliverOrderUseCase execute state transitions', function () {
    $repository = m::mock(OrderRepositoryInterface::class);

    $item = OrderItem::create('p-1', 'Prod 1', 'SKU-1', 10.0, 1);
    $order = Order::create('cust-1', 'cash', [$item]);

    $repository->shouldReceive('findById')->times(4)->andReturn($order);
    $repository->shouldReceive('save')->times(4)->with($order);

    // 1. Confirm
    $confirmUseCase = new ConfirmOrderUseCase($repository);
    $confirmUseCase->execute($order->id()->value());
    expect($order->status())->toBe(OrderStatus::CONFIRMED);

    // 2. Process
    $processUseCase = new ProcessOrderUseCase($repository);
    $processUseCase->execute($order->id()->value());
    expect($order->status())->toBe(OrderStatus::PROCESSING);

    // 3. Ship
    $shipUseCase = new ShipOrderUseCase($repository);
    $shipUseCase->execute($order->id()->value(), 'Starken');
    expect($order->status())->toBe(OrderStatus::SHIPPED)
        ->and($order->shippingMethod())->toBe('Starken');

    // 4. Deliver
    $deliverUseCase = new DeliverOrderUseCase($repository);
    $deliverUseCase->execute($order->id()->value());
    expect($order->status())->toBe(OrderStatus::DELIVERED);
});

it('CancelOrderUseCase and RefundOrderUseCase transition order status', function () {
    $repository = m::mock(OrderRepositoryInterface::class);

    $item = OrderItem::create('p-1', 'Prod 1', 'SKU-1', 10.0, 1);
    $order = Order::create('cust-1', 'cash', [$item]);

    $repository->shouldReceive('findById')->once()->andReturn($order);
    $repository->shouldReceive('save')->once()->with($order);

    // Fase 1.2 (hallazgo D2): cancelar revierte también la comisión de la
    // plataforma. Aquí se comprueba que se invoque; su lógica se prueba en
    // tests/Feature/Monetization/TenantMonetizationAndCommissionTest.php.
    $reverseCommission = m::mock(\Src\Monetization\Application\UseCases\ReverseOrderCommissionUseCase::class);
    $reverseCommission->shouldReceive('execute')->once();

    // Hallazgo N13: cancelar repone el stock. Aqui se comprueba que se invoque una vez
    // por linea; el descuento y la reposicion reales se prueban en
    // tests/Feature/Product/CentralCatalogSyncTest.php.
    $stockReserver = m::mock(\Src\Marketplace\Application\Service\StockReserver::class);
    $stockReserver->shouldReceive('release')->once();

    $cancelUseCase = new CancelOrderUseCase($repository, $reverseCommission, $stockReserver);
    $cancelUseCase->execute($order->id()->value(), 'Cliente cambió de opinión');

    expect($order->status())->toBe(OrderStatus::CANCELLED);
});

it('UpdateOrderPaymentStatusUseCase updates payment status', function () {
    $repository = m::mock(OrderRepositoryInterface::class);

    $item = OrderItem::create('p-1', 'Prod 1', 'SKU-1', 10.0, 1);
    $order = Order::create('cust-1', 'cash', [$item]);

    $repository->shouldReceive('findById')->once()->andReturn($order);
    $repository->shouldReceive('save')->once()->with($order);

    // Hallazgo N15: al confirmar el pago, la comision pasa de `awaiting_payment` a
    // `pending`. Aqui se comprueba que se invoque; su logica se prueba en
    // tests/Feature/Monetization/TenantMonetizationAndCommissionTest.php.
    $activateCommission = m::mock(\Src\Monetization\Application\UseCases\ActivateOrderCommissionUseCase::class);
    $activateCommission->shouldReceive('execute')->once();

    $paymentUseCase = new UpdateOrderPaymentStatusUseCase($repository, $activateCommission);
    $paymentUseCase->execute($order->id()->value(), 'paid');

    expect($order->paymentStatus())->toBe(PaymentStatus::PAID);
});

it('GetOrderMetricsUseCase returns aggregated order metrics', function () {
    $repository = m::mock(OrderRepositoryInterface::class);
    $metrics = new OrderMetricsData(
        totalOrders: 10,
        pendingOrders: 2,
        processingOrders: 3,
        completedOrders: 5,
        totalSalesAmount: 1500.0,
        averageOrderValue: 150.0
    );

    $repository->shouldReceive('getMetrics')->once()->andReturn($metrics);

    $useCase = new GetOrderMetricsUseCase($repository);
    $result = $useCase->execute();

    expect($result->totalOrders)->toBe(10)
        ->and($result->totalSalesAmount)->toBe(1500.0);
});
