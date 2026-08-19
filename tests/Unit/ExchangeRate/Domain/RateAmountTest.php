<?php

declare(strict_types=1);

use Src\ExchangeRate\Domain\ValueObjects\RateAmount;

test('RateAmount accepts float, integer and string with comma or dot', function () {
    $rate1 = RateAmount::make(41.0245);
    $rate2 = RateAmount::make('775,33560000');
    $rate3 = RateAmount::make(100);

    expect($rate1->value())->toBe(41.0245);
    expect($rate2->value())->toBe(775.3356);
    expect($rate3->value())->toBe(100.0);
});

test('RateAmount correctly multiplies and divides currency amounts', function () {
    $rate = RateAmount::make(40.0);

    // 25 USD * 40 = 1000 VES
    expect($rate->multiply(25.0))->toBe(1000.0);

    // 1000 VES / 40 = 25 USD
    expect($rate->divide(1000.0))->toBe(25.0);
});

test('RateAmount rejects non-numeric or non-positive values with 400 code', function () {
    expect(fn () => RateAmount::make(0))->toThrow(InvalidArgumentException::class);
    expect(fn () => RateAmount::make(-5.5))->toThrow(InvalidArgumentException::class);
    expect(fn () => RateAmount::make('invalid-rate'))->toThrow(InvalidArgumentException::class);
});
