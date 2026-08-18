<?php

declare(strict_types=1);

use Src\Payment\Domain\ValueObjects\PaymentId;
use Src\Payment\Domain\ValueObjects\PaymentMethod;
use Src\Payment\Domain\ValueObjects\PaymentStatus;
use Src\Payment\Infrastructure\Adapters\CashOnDeliveryPaymentGateway;
use Src\Payment\Infrastructure\Adapters\ManualBankTransferPaymentGateway;

it('creates and validates PaymentId and PaymentMethod correctly', function () {
    $id = PaymentId::random();
    expect($id->value())->toBeString()
        ->and(strlen($id->value()))->toBe(36);

    $method = PaymentMethod::fromString('MANUAL_TRANSFER');
    expect($method->value())->toBe('manual_transfer')
        ->and($method->isManual())->toBeTrue();
});

it('creates and validates PaymentStatus states correctly', function () {
    $status = PaymentStatus::completed();
    expect($status->isCompleted())->toBeTrue()
        ->and($status->isPending())->toBeFalse();

    expect(fn () => PaymentStatus::fromString('invalid_status'))
        ->toThrow(InvalidArgumentException::class);
});

it('ManualBankTransferPaymentGateway charges, refunds and handles webhooks', function () {
    $gateway = new ManualBankTransferPaymentGateway([
        'instructions' => 'Transferir a Banco Santander Cta Cte 123456',
    ]);

    expect($gateway->getIdentifier())->toBe('manual_transfer')
        ->and($gateway->isOffline())->toBeTrue();

    $chargeResult = $gateway->charge([
        'order_id' => 'ORD-001',
        'amount' => 1500.00,
        'currency' => 'USD',
        'customer_email' => 'juan@test.com',
        'customer_name' => 'Juan',
    ]);

    expect($chargeResult->success)->toBeTrue()
        ->and($chargeResult->status->isPending())->toBeTrue()
        ->and($chargeResult->instructions)->toContain('Banco Santander')
        ->and($chargeResult->transactionId)->toStartWith('TX-BT-');

    $refundResult = $gateway->refund('TX-BT-123', 500.00, 'Devolución parcial');
    expect($refundResult->success)->toBeTrue()
        ->and($refundResult->refundedAmount)->toBe(500.00)
        ->and($refundResult->refundId)->toStartWith('REF-BT-');

    $webhookResult = $gateway->handleWebhook(['event' => 'ping']);
    expect($webhookResult->handled)->toBeFalse();
});

it('CashOnDeliveryPaymentGateway charges and refunds properly', function () {
    $gateway = new CashOnDeliveryPaymentGateway;

    expect($gateway->getIdentifier())->toBe('cash_on_delivery')
        ->and($gateway->isOffline())->toBeTrue();

    $result = $gateway->charge([
        'order_id' => 'ORD-002',
        'amount' => 200.00,
        'currency' => 'USD',
        'customer_email' => 'pedro@test.com',
        'customer_name' => 'Pedro',
    ]);

    expect($result->success)->toBeTrue()
        ->and($result->status->isPending())->toBeTrue()
        ->and($result->transactionId)->toStartWith('TX-COD-');
});
