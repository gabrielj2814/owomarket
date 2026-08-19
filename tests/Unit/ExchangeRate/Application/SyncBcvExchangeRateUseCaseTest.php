<?php

declare(strict_types=1);

use Src\ExchangeRate\Application\UseCase\SyncBcvExchangeRateUseCase;
use Src\ExchangeRate\Domain\Contracts\BcvScraperInterface;
use Src\ExchangeRate\Domain\Contracts\ExchangeRateRepositoryInterface;
use Src\ExchangeRate\Domain\Entities\ExchangeRate;
use Src\ExchangeRate\Domain\ValueObjects\CurrencyCode;
use Src\ExchangeRate\Domain\ValueObjects\RateAmount;
use Src\ExchangeRate\Domain\ValueObjects\RateDate;
use Src\ExchangeRate\Domain\ValueObjects\RateSource;
use Src\Shared\Infrastructure\Security\LaravelUuidGenerator;

test('SyncBcvExchangeRateUseCase deactivates old rates and persists new scraped rate', function () {
    $generator = new LaravelUuidGenerator;

    $mockScraper = Mockery::mock(BcvScraperInterface::class);
    $mockScraper->shouldReceive('fetchUsdRate')->once()->andReturn([
        'rate' => 775.3356,
        'rate_date' => '2026-08-19',
        'raw_html' => '<strong>775,33560000</strong>',
        'success' => true,
        'error_message' => null,
    ]);

    $mockRepo = Mockery::mock(ExchangeRateRepositoryInterface::class);
    $mockRepo->shouldReceive('deactivateAll')->once()->with(
        Mockery::on(fn (CurrencyCode $c) => $c->isUsd()),
        Mockery::on(fn (CurrencyCode $c) => $c->isVes())
    );
    $mockRepo->shouldReceive('save')->once()->with(
        Mockery::on(fn (ExchangeRate $r) => $r->getRate()->value() === 775.3356 && $r->isActive())
    );

    $useCase = new SyncBcvExchangeRateUseCase($mockScraper, $mockRepo, $generator);
    $result = $useCase->execute();

    expect($result->getRate()->value())->toBe(775.3356);
    expect($result->getSource()->value())->toBe('BCV_SCRAPING');
    expect($result->isActive())->toBeTrue();
});

test('SyncBcvExchangeRateUseCase falls back to active rate if scraping fails', function () {
    $generator = new LaravelUuidGenerator;

    $mockScraper = Mockery::mock(BcvScraperInterface::class);
    $mockScraper->shouldReceive('fetchUsdRate')->once()->andReturn([
        'rate' => 0.0,
        'rate_date' => '2026-08-19',
        'raw_html' => null,
        'success' => false,
        'error_message' => 'Timeout',
    ]);

    $existingActive = ExchangeRate::create(
        $generator,
        CurrencyCode::usd(),
        CurrencyCode::ves(),
        RateAmount::make(750.0),
        RateSource::bcv(),
        RateDate::today()
    );

    $mockRepo = Mockery::mock(ExchangeRateRepositoryInterface::class);
    $mockRepo->shouldReceive('findActive')->once()->andReturn($existingActive);

    $useCase = new SyncBcvExchangeRateUseCase($mockScraper, $mockRepo, $generator);
    $result = $useCase->execute();

    expect($result->getRate()->value())->toBe(750.0);
});
