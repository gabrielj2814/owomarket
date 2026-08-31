<?php

declare(strict_types=1);

use Ramsey\Uuid\Uuid;
use Src\Shipment\Application\DTOs\CreateShipmentData;
use Src\Shipment\Application\DTOs\FilterShipmentsCriteria;
use Src\Shipment\Application\DTOs\PaginatedShipmentResult;
use Src\Shipment\Application\DTOs\ShipmentMetricsData;
use Src\Shipment\Application\DTOs\UpdateTrackingData;
use Src\Shipment\Application\Repositories\ShipmentRepositoryInterface;
use Src\Shipment\Application\UseCases\ConsultShipmentByIdUseCase;
use Src\Shipment\Application\UseCases\ConsultShipmentByOrderIdUseCase;
use Src\Shipment\Application\UseCases\CreateShipmentUseCase;
use Src\Shipment\Application\UseCases\FilterShipmentsUseCase;
use Src\Shipment\Application\UseCases\GetShipmentMetricsUseCase;
use Src\Shipment\Application\UseCases\MarkShipmentAsDeliveredUseCase;
use Src\Shipment\Application\UseCases\UpdateShipmentTrackingUseCase;
use Src\Shipment\Domain\Entities\Shipment;
use Src\Shipment\Domain\Exceptions\ShipmentNotFoundException;
use Src\Shipment\Domain\ValueObjects\ShipmentId;

beforeEach(function () {
    $this->repository = Mockery::mock(ShipmentRepositoryInterface::class);
});

afterEach(function () {
    Mockery::close();
});

it('CreateShipmentUseCase creates and saves shipment entity', function () {
    $orderId = Uuid::uuid4()->toString();
    $dto = new CreateShipmentData(
        orderId: $orderId,
        carrier: 'Chilexpress',
        service: 'Express 24h',
        cost: 15.00,
        trackingNumber: 'CHX-12345678',
        notes: 'Notas de entrega'
    );

    $this->repository->shouldReceive('save')
        ->once()
        ->with(Mockery::type(Shipment::class))
        ->andReturnUsing(fn (Shipment $s) => $s);

    $useCase = new CreateShipmentUseCase($this->repository);
    $result = $useCase->execute($dto);

    expect($result->orderId())->toBe($orderId)
        ->and($result->carrier()->value())->toBe('Chilexpress')
        ->and($result->service()->value())->toBe('Express 24h')
        ->and($result->cost()->amount())->toBe(15.00)
        ->and($result->trackingNumber()?->value())->toBe('CHX-12345678')
        ->and($result->notes())->toBe('Notas de entrega');
});

it('UpdateShipmentTrackingUseCase updates tracking and carrier info', function () {
    $shipment = Shipment::create(
        orderId: Uuid::uuid4()->toString(),
        carrier: 'Starken',
        service: 'Standard'
    );
    $id = $shipment->id()->value();

    $this->repository->shouldReceive('findById')
        ->once()
        ->with(Mockery::type(ShipmentId::class))
        ->andReturn($shipment);

    $this->repository->shouldReceive('save')
        ->once()
        ->with($shipment)
        ->andReturn($shipment);

    $updateDto = new UpdateTrackingData(
        trackingNumber: 'STA-99881122',
        carrier: 'Starken Express',
        service: 'Priority',
        cost: 18.50,
        notes: 'Actualizado courier'
    );

    $useCase = new UpdateShipmentTrackingUseCase($this->repository);
    $updated = $useCase->execute($id, $updateDto);

    expect($updated->trackingNumber()?->value())->toBe('STA-99881122')
        ->and($updated->carrier()->value())->toBe('Starken Express')
        ->and($updated->service()->value())->toBe('Priority')
        ->and($updated->cost()->amount())->toBe(18.50)
        ->and($updated->notes())->toBe('Actualizado courier');
});

it('UpdateShipmentTrackingUseCase throws exception when shipment not found', function () {
    $this->repository->shouldReceive('findById')
        ->once()
        ->andReturnNull();

    $useCase = new UpdateShipmentTrackingUseCase($this->repository);
    $useCase->execute(Uuid::uuid4()->toString(), new UpdateTrackingData('TRK-123'));
})->throws(ShipmentNotFoundException::class);

it('MarkShipmentAsDeliveredUseCase sets delivered status and timestamps', function () {
    $shipment = Shipment::create(
        orderId: Uuid::uuid4()->toString(),
        carrier: 'DHL',
        service: 'Express',
        trackingNumber: 'DHL-991122'
    );
    $id = $shipment->id()->value();

    $this->repository->shouldReceive('findById')
        ->once()
        ->with(Mockery::type(ShipmentId::class))
        ->andReturn($shipment);

    $this->repository->shouldReceive('save')
        ->once()
        ->with($shipment)
        ->andReturn($shipment);

    // Fase 4b: si el pedido queda entregado, se libera su comision. Con el pedido fuera de
    // alcance en este test unitario el repositorio devuelve null y no se libera nada -- que
    // es el comportamiento correcto: no se da por entregado lo que no consta.
    $orders = Mockery::mock(Src\Order\Application\Contracts\Repositories\OrderRepositoryInterface::class);
    $orders->shouldReceive('findById')->once()->andReturn(null);

    $liberar = Mockery::mock(Src\Monetization\Application\UseCases\ReleaseOrderCommissionUseCase::class);
    $liberar->shouldNotReceive('execute');

    $useCase = new MarkShipmentAsDeliveredUseCase($this->repository, $orders, $liberar);
    $delivered = $useCase->execute($id);

    expect($delivered->isDelivered())->toBeTrue()
        ->and($delivered->deliveredAt())->not->toBeNull();
});

it('ConsultShipmentByIdUseCase returns shipment or throws exception', function () {
    $shipment = Shipment::create(
        orderId: Uuid::uuid4()->toString(),
        carrier: 'FedEx',
        service: 'Ground'
    );
    $id = $shipment->id()->value();

    $this->repository->shouldReceive('findById')
        ->once()
        ->with(Mockery::type(ShipmentId::class))
        ->andReturn($shipment);

    $useCase = new ConsultShipmentByIdUseCase($this->repository);
    $result = $useCase->execute($id);

    expect($result->id()->value())->toBe($id);
});

it('ConsultShipmentByOrderIdUseCase returns shipments array for order', function () {
    $orderId = Uuid::uuid4()->toString();
    $shipments = [
        Shipment::create($orderId, 'DHL', 'Express'),
    ];

    $this->repository->shouldReceive('findByOrderId')
        ->once()
        ->with($orderId)
        ->andReturn($shipments);

    $useCase = new ConsultShipmentByOrderIdUseCase($this->repository);
    $result = $useCase->execute($orderId);

    expect($result)->toHaveCount(1)
        ->and($result[0]->orderId())->toBe($orderId);
});

it('FilterShipmentsUseCase delegates to repository filter method', function () {
    $criteria = new FilterShipmentsCriteria(carrier: 'Chilexpress', page: 1, perPage: 10);
    $expected = new PaginatedShipmentResult([], 0, 10, 1, 1);

    $this->repository->shouldReceive('filter')
        ->once()
        ->with($criteria)
        ->andReturn($expected);

    $useCase = new FilterShipmentsUseCase($this->repository);
    $result = $useCase->execute($criteria);

    expect($result->total)->toBe(0);
});

it('GetShipmentMetricsUseCase returns metrics dto', function () {
    $metrics = new ShipmentMetricsData(10, 2, 3, 5, 250.75);

    $this->repository->shouldReceive('getMetrics')
        ->once()
        ->andReturn($metrics);

    $useCase = new GetShipmentMetricsUseCase($this->repository);
    $result = $useCase->execute();

    expect($result->totalShipments)->toBe(10)
        ->and($result->deliveredShipments)->toBe(5)
        ->and($result->totalShippingCost)->toBe(250.75);
});
