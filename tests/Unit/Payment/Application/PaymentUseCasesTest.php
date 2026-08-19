<?php

declare(strict_types=1);

use Src\Payment\Application\Contracts\PaymentGatewayFactoryInterface;
use Src\Payment\Application\DTOs\ProcessPaymentData;
use Src\Payment\Application\DTOs\RefundPaymentData;
use Src\Payment\Application\UseCases\ListAvailablePaymentGatewaysUseCase;
use Src\Payment\Application\UseCases\ProcessPaymentUseCase;
use Src\Payment\Application\UseCases\RefundPaymentUseCase;
use Src\Payment\Domain\Contracts\PaymentGatewayInterface;
use Src\Payment\Domain\Exceptions\PaymentGatewayNotFoundException;
use Src\Payment\Domain\ValueObjects\PaymentResult;
use Src\Payment\Domain\ValueObjects\RefundResult;
use Src\Payment\Infrastructure\Adapters\ManualBankTransferPaymentGateway;

it('ProcessPaymentUseCase resolves gateway from factory and processes payment', function () {
    $factory = Mockery::mock(PaymentGatewayFactoryInterface::class);
    $gateway = Mockery::mock(PaymentGatewayInterface::class);

    $factory->shouldReceive('make')
        ->once()
        ->with('manual_transfer')
        ->andReturn($gateway);

    $gateway->shouldReceive('charge')
        ->once()
        ->andReturn(PaymentResult::completed('TX-123', 'Pago recibido'));

    $useCase = new ProcessPaymentUseCase($factory);

    $dto = new ProcessPaymentData(
        amount: 250.00,
        currency: 'USD',
        customer_email: 'cliente@test.com',
        customer_name: 'Cliente Demo',
        payment_method: 'manual_transfer'
    );

    $result = $useCase->execute($dto);

    expect($result->success)->toBeTrue()
        ->and($result->status->isCompleted())->toBeTrue()
        ->and($result->transactionId)->toBe('TX-123');
});

it('ProcessPaymentUseCase throws exception if gateway is not registered', function () {
    $factory = Mockery::mock(PaymentGatewayFactoryInterface::class);
    $factory->shouldReceive('make')
        ->once()
        ->with('unknown_gateway')
        ->andThrow(PaymentGatewayNotFoundException::withIdentifier('unknown_gateway'));

    $useCase = new ProcessPaymentUseCase($factory);

    $dto = new ProcessPaymentData(
        amount: 100.00,
        currency: 'USD',
        customer_email: 'c@test.com',
        customer_name: 'C',
        payment_method: 'unknown_gateway'
    );

    expect(fn () => $useCase->execute($dto))
        ->toThrow(PaymentGatewayNotFoundException::class);
});

it('RefundPaymentUseCase resolves gateway and executes refund', function () {
    $factory = Mockery::mock(PaymentGatewayFactoryInterface::class);
    $gateway = Mockery::mock(PaymentGatewayInterface::class);

    $factory->shouldReceive('make')
        ->once()
        ->with('manual_transfer')
        ->andReturn($gateway);

    $gateway->shouldReceive('refund')
        ->once()
        ->with('TX-999', 100.00, 'Cancelación')
        ->andReturn(RefundResult::success('REF-1', 100.00));

    $useCase = new RefundPaymentUseCase($factory);

    $dto = new RefundPaymentData(
        transaction_id: 'TX-999',
        amount: 100.00,
        payment_method: 'manual_transfer',
        reason: 'Cancelación'
    );

    $result = $useCase->execute($dto);

    expect($result->success)->toBeTrue()
        ->and($result->refundId)->toBe('REF-1');
});

it('ListAvailablePaymentGatewaysUseCase returns list of registered gateways with info', function () {
    $factory = Mockery::mock(PaymentGatewayFactoryInterface::class);
    $gateway = new ManualBankTransferPaymentGateway;

    $factory->shouldReceive('getRegisteredGateways')
        ->once()
        ->andReturn(['manual_transfer' => ManualBankTransferPaymentGateway::class]);

    $factory->shouldReceive('make')
        ->once()
        ->with('manual_transfer')
        ->andReturn($gateway);

    $useCase = new ListAvailablePaymentGatewaysUseCase($factory);
    $list = $useCase->execute();

    expect($list)->toHaveCount(1)
        ->and($list[0]->identifier)->toBe('manual_transfer')
        ->and($list[0]->is_offline)->toBeTrue();
});
