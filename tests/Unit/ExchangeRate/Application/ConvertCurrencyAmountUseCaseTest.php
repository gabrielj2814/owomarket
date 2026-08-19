<?php

declare(strict_types=1);

use Src\ExchangeRate\Application\UseCase\ConvertCurrencyAmountUseCase;
use Src\ExchangeRate\Domain\Contracts\ExchangeRateRepositoryInterface;
use Src\ExchangeRate\Domain\Entities\ExchangeRate;
use Src\ExchangeRate\Domain\ValueObjects\CurrencyCode;
use Src\ExchangeRate\Domain\ValueObjects\RateAmount;
use Src\ExchangeRate\Domain\ValueObjects\RateDate;
use Src\ExchangeRate\Domain\ValueObjects\RateSource;
use Src\Shared\Infrastructure\Security\LaravelUuidGenerator;

test('ConvertCurrencyAmountUseCase accurately converts USD to VES and VES to USD', function () {
    $generator = new LaravelUuidGenerator;
    $rate = ExchangeRate::create(
        $generator,
        CurrencyCode::usd(),
        CurrencyCode::ves(),
        RateAmount::make(40.0),
        RateSource::bcv(),
        RateDate::today()
    );

    $mockRepo = Mockery::mock(ExchangeRateRepositoryInterface::class);
    $mockRepo->shouldReceive('findActive')->andReturn($rate);

    $useCase = new ConvertCurrencyAmountUseCase($mockRepo);

    $usdConversion = $useCase->usdToVes(25.0);
    expect($usdConversion['amount_usd'])->toBe(25.0);
    expect($usdConversion['amount_ves'])->toBe(1000.0);
    expect($usdConversion['rate'])->toBe(40.0);

    $vesConversion = $useCase->vesToUsd(1000.0);
    expect($vesConversion['amount_ves'])->toBe(1000.0);
    expect($vesConversion['amount_usd'])->toBe(25.0);
});
