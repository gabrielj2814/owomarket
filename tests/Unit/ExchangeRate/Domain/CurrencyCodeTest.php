<?php

declare(strict_types=1);

use Src\ExchangeRate\Domain\ValueObjects\CurrencyCode;

test('CurrencyCode handles USD, VES, USDT, USDC, EUR', function () {
    $usd = CurrencyCode::usd();
    $ves = CurrencyCode::ves();
    $usdt = CurrencyCode::usdt();
    $usdc = CurrencyCode::usdc();

    expect($usd->isUsd())->toBeTrue();
    expect($ves->isVes())->toBeTrue();
    expect($usdt->isCrypto())->toBeTrue();
    expect($usdc->isCrypto())->toBeTrue();
    expect($usd->value())->toBe('USD');
    expect($ves->value())->toBe('VES');
});

test('CurrencyCode rejects unsupported currencies', function () {
    expect(fn () => CurrencyCode::make('XYZ'))->toThrow(InvalidArgumentException::class);
});
