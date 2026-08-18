<?php

declare(strict_types=1);

use Src\Order\Domain\ValueObjects\Currency;
use Src\Order\Domain\ValueObjects\Money;
use Src\Order\Domain\ValueObjects\OrderNumber;
use Src\Order\Domain\ValueObjects\OrderStatus;
use Src\Order\Domain\ValueObjects\PaymentStatus;

it('tests OrderStatus transitions and helpers', function () {
    $pending = OrderStatus::fromString('pending');
    expect($pending->isPending())->toBeTrue()
        ->and($pending->canBeConfirmed())->toBeTrue()
        ->and($pending->canBeCancelled())->toBeTrue()
        ->and($pending->canBeShipped())->toBeFalse();

    $confirmed = OrderStatus::fromString('confirmed');
    expect($confirmed->isConfirmed())->toBeTrue()
        ->and($confirmed->canBeProcessed())->toBeTrue()
        ->and($confirmed->canBeShipped())->toBeTrue()
        ->and($confirmed->canBeRefunded())->toBeTrue();

    $shipped = OrderStatus::fromString('shipped');
    expect($shipped->isShipped())->toBeTrue()
        ->and($shipped->canBeDelivered())->toBeTrue()
        ->and($shipped->canBeCancelled())->toBeFalse();

    $delivered = OrderStatus::fromString('delivered');
    expect($delivered->isDelivered())->toBeTrue()
        ->and($delivered->canBeRefunded())->toBeTrue()
        ->and($delivered->canBeCancelled())->toBeFalse();
});

it('throws exception on invalid OrderStatus string', function () {
    OrderStatus::fromString('invalid_status');
})->throws(InvalidArgumentException::class);

it('tests PaymentStatus helpers', function () {
    $paid = PaymentStatus::fromString('paid');
    expect($paid->isPaid())->toBeTrue()
        ->and($paid->isPending())->toBeFalse();

    $failed = PaymentStatus::fromString('failed');
    expect($failed->isFailed())->toBeTrue();
});

it('tests Money value object operations', function () {
    $m1 = Money::from(10.50);
    $m2 = Money::from(5.25);

    expect($m1->add($m2)->amount())->toBe(15.75)
        ->and($m1->subtract($m2)->amount())->toBe(5.25)
        ->and($m2->multiply(2)->amount())->toBe(10.50)
        ->and($m1->isGreaterThan($m2))->toBeTrue()
        ->and($m1->formatted('$'))->toBe('$ 10.50');
});

it('throws exception on negative Money', function () {
    new Money(-10);
})->throws(InvalidArgumentException::class);

it('tests OrderNumber generation and validation', function () {
    $orderNumber = OrderNumber::generate('ORD');
    expect($orderNumber->value())->toStartWith('ORD-');

    $customNumber = new OrderNumber('MY-ORDER-123');
    expect($customNumber->value())->toBe('MY-ORDER-123');
});

it('throws exception on invalid OrderNumber', function () {
    new OrderNumber('   ');
})->throws(InvalidArgumentException::class);

it('tests Currency value object', function () {
    $currency = new Currency('clp');
    expect($currency->code())->toBe('CLP');
});

it('throws exception on invalid Currency code', function () {
    new Currency('CHILE');
})->throws(InvalidArgumentException::class);
