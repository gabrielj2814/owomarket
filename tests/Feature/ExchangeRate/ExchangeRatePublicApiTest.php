<?php

declare(strict_types=1);

use Src\ExchangeRate\Domain\Contracts\ExchangeRateRepositoryInterface;
use Src\ExchangeRate\Domain\Entities\ExchangeRate;
use Src\ExchangeRate\Domain\ValueObjects\CurrencyCode;
use Src\ExchangeRate\Domain\ValueObjects\RateAmount;
use Src\ExchangeRate\Domain\ValueObjects\RateDate;
use Src\ExchangeRate\Domain\ValueObjects\RateSource;
use Src\Shared\Infrastructure\Security\LaravelUuidGenerator;

test('GET /api/exchange-rate/current returns active rate JSON', function () {
    $generator = new LaravelUuidGenerator;
    $repository = app(ExchangeRateRepositoryInterface::class);

    $rate = ExchangeRate::create(
        $generator,
        CurrencyCode::usd(),
        CurrencyCode::ves(),
        RateAmount::make(775.3356),
        RateSource::bcv(),
        RateDate::today()
    );
    $repository->save($rate);

    $response = $this->getJson('/api/exchange-rate/current');

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'data' => [
                'base_currency' => 'USD',
                'target_currency' => 'VES',
                'rate' => 775.3356,
                'source' => 'BCV_SCRAPING',
                'is_active' => true,
            ],
        ]);
});

test('GET /api/exchange-rate/convert calculates USD to VES conversion', function () {
    $generator = new LaravelUuidGenerator;
    $repository = app(ExchangeRateRepositoryInterface::class);

    $rate = ExchangeRate::create(
        $generator,
        CurrencyCode::usd(),
        CurrencyCode::ves(),
        RateAmount::make(40.0),
        RateSource::bcv(),
        RateDate::today()
    );
    $repository->save($rate);

    $response = $this->getJson('/api/exchange-rate/convert?amount=25&from=USD&to=VES');

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'data' => [
                'amount_usd' => 25.0,
                'amount_ves' => 1000.0,
                'rate' => 40.0,
            ],
        ]);
});
