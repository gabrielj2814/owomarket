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

// El caso de uso envuelve `deactivateAll()` + `save()` en `DB::transaction` (hallazgo D3),
// así que necesita la aplicación levantada aunque los colaboradores estén mockeados.
uses(Tests\TestCase::class);

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

// Hallazgo D4: el fallback sólo dejaba un `warning`, así que el sitio podía pasar semanas
// facturando con una tasa congelada sin que nadie se enterara.
test('SyncBcvExchangeRateUseCase escalates to error when the fallback rate is stale', function () {
    $generator = new LaravelUuidGenerator;

    $mockScraper = Mockery::mock(BcvScraperInterface::class);
    $mockScraper->shouldReceive('fetchUsdRate')->once()->andReturn([
        'rate' => 0.0,
        'rate_date' => date('Y-m-d'),
        'raw_html' => null,
        'success' => false,
        'error_message' => 'Timeout',
    ]);

    $staleRate = ExchangeRate::create(
        $generator,
        CurrencyCode::usd(),
        CurrencyCode::ves(),
        RateAmount::make(750.0),
        RateSource::bcv(),
        RateDate::make((new DateTimeImmutable('today'))->modify('-5 days')->format('Y-m-d'))
    );

    $mockRepo = Mockery::mock(ExchangeRateRepositoryInterface::class);
    $mockRepo->shouldReceive('findActive')->once()->andReturn($staleRate);

    $mockLogger = Mockery::mock(Psr\Log\LoggerInterface::class);
    $mockLogger->shouldNotReceive('warning');
    $mockLogger->shouldReceive('error')->once()->with(
        Mockery::on(fn (string $message) => str_contains($message, '5 días sin actualizarse')),
        Mockery::on(fn (array $context) => $context['days_stale'] === 5)
    );

    $useCase = new SyncBcvExchangeRateUseCase($mockScraper, $mockRepo, $generator, $mockLogger);
    $result = $useCase->execute();

    expect($result->getRate()->value())->toBe(750.0);
});

test('SyncBcvExchangeRateUseCase only warns when the fallback rate is still fresh', function () {
    $generator = new LaravelUuidGenerator;

    $mockScraper = Mockery::mock(BcvScraperInterface::class);
    $mockScraper->shouldReceive('fetchUsdRate')->once()->andReturn([
        'rate' => 0.0,
        'rate_date' => date('Y-m-d'),
        'raw_html' => null,
        'success' => false,
        'error_message' => 'Timeout',
    ]);

    $freshRate = ExchangeRate::create(
        $generator,
        CurrencyCode::usd(),
        CurrencyCode::ves(),
        RateAmount::make(750.0),
        RateSource::bcv(),
        RateDate::today()
    );

    $mockRepo = Mockery::mock(ExchangeRateRepositoryInterface::class);
    $mockRepo->shouldReceive('findActive')->once()->andReturn($freshRate);

    $mockLogger = Mockery::mock(Psr\Log\LoggerInterface::class);
    $mockLogger->shouldNotReceive('error');
    $mockLogger->shouldReceive('warning')->once();

    $useCase = new SyncBcvExchangeRateUseCase($mockScraper, $mockRepo, $generator, $mockLogger);

    expect($useCase->execute()->getRate()->value())->toBe(750.0);
});
