<?php

declare(strict_types=1);

use Src\ExchangeRate\Domain\Entities\ExchangeRate;
use Src\ExchangeRate\Domain\ValueObjects\CurrencyCode;
use Src\ExchangeRate\Domain\ValueObjects\RateAmount;
use Src\ExchangeRate\Domain\ValueObjects\RateDate;
use Src\ExchangeRate\Domain\ValueObjects\RateSource;
use Src\Shared\Infrastructure\Security\LaravelUuidGenerator;

test('ExchangeRate entity creates and converts amounts correctly', function () {
    $generator = new LaravelUuidGenerator;

    $exchangeRate = ExchangeRate::create(
        $generator,
        CurrencyCode::usd(),
        CurrencyCode::ves(),
        RateAmount::make(40.0),
        RateSource::bcv(),
        RateDate::today()
    );

    expect($exchangeRate->isActive())->toBeTrue();
    expect($exchangeRate->getBaseCurrency()->value())->toBe('USD');
    expect($exchangeRate->getTargetCurrency()->value())->toBe('VES');
    expect($exchangeRate->convertUsdToVes(50.0))->toBe(2000.0);
    expect($exchangeRate->convertVesToUsd(2000.0))->toBe(50.0);

    $exchangeRate->deactivate();
    expect($exchangeRate->isActive())->toBeFalse();
});
