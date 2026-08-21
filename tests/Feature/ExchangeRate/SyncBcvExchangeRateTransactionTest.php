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
use Src\Shared\Domain\ValueObjects\Uuid;
use Src\Shared\Infrastructure\Security\LaravelUuidGenerator;

/**
 * Hallazgo D3: `deactivateAll()` y `save()` eran dos escrituras sueltas. Si la segunda
 * fallaba, el sistema quedaba sin ninguna tasa activa y todas las conversiones pasaban a
 * usar el fallback de 1.0 — el checkout en bolívares cobraba céntimos.
 */
test('SyncBcvExchangeRateUseCase leaves the previous rate active if persisting the new one fails', function () {
    $generator = new LaravelUuidGenerator;
    $realRepository = app(ExchangeRateRepositoryInterface::class);

    $previousRate = ExchangeRate::create(
        $generator,
        CurrencyCode::usd(),
        CurrencyCode::ves(),
        RateAmount::make(775.3356),
        RateSource::bcv(),
        RateDate::today()
    );
    $realRepository->save($previousRate);

    $mockScraper = Mockery::mock(BcvScraperInterface::class);
    $mockScraper->shouldReceive('fetchUsdRate')->once()->andReturn([
        'rate' => 800.0,
        'rate_date' => date('Y-m-d'),
        'raw_html' => '<strong>800,00000000</strong>',
        'success' => true,
        'error_message' => null,
    ]);

    // Desactiva de verdad (para que haya algo que revertir) pero revienta al persistir.
    $failingRepository = new class($realRepository) implements ExchangeRateRepositoryInterface
    {
        public function __construct(private readonly ExchangeRateRepositoryInterface $inner) {}

        public function save(ExchangeRate $exchangeRate): void
        {
            throw new RuntimeException('Fallo al persistir la nueva tasa');
        }

        public function findActive(CurrencyCode $baseCurrency, CurrencyCode $targetCurrency): ?ExchangeRate
        {
            return $this->inner->findActive($baseCurrency, $targetCurrency);
        }

        public function findById(Uuid $id): ?ExchangeRate
        {
            return $this->inner->findById($id);
        }

        public function deactivateAll(CurrencyCode $baseCurrency, CurrencyCode $targetCurrency): void
        {
            $this->inner->deactivateAll($baseCurrency, $targetCurrency);
        }

        public function listHistory(
            int $page = 1,
            int $perPage = 15,
            ?string $source = null,
            ?string $dateFrom = null,
            ?string $dateTo = null
        ): array {
            return $this->inner->listHistory($page, $perPage, $source, $dateFrom, $dateTo);
        }
    };

    $useCase = new SyncBcvExchangeRateUseCase($mockScraper, $failingRepository, $generator);

    expect(fn () => $useCase->execute())->toThrow(RuntimeException::class);

    $active = $realRepository->findActive(CurrencyCode::usd(), CurrencyCode::ves());

    expect($active)->not->toBeNull()
        ->and($active->getRate()->value())->toBe(775.3356);
});
