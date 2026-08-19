<?php

declare(strict_types=1);

namespace Src\ExchangeRate\Application\UseCase;

use Exception;
use Psr\Log\LoggerInterface;
use Src\ExchangeRate\Domain\Contracts\BcvScraperInterface;
use Src\ExchangeRate\Domain\Contracts\ExchangeRateRepositoryInterface;
use Src\ExchangeRate\Domain\Entities\ExchangeRate;
use Src\ExchangeRate\Domain\ValueObjects\CurrencyCode;
use Src\ExchangeRate\Domain\ValueObjects\RateAmount;
use Src\ExchangeRate\Domain\ValueObjects\RateDate;
use Src\ExchangeRate\Domain\ValueObjects\RateSource;
use Src\Shared\Domain\Contracts\UuidGenerator;

final class SyncBcvExchangeRateUseCase
{
    public function __construct(
        private readonly BcvScraperInterface $scraper,
        private readonly ExchangeRateRepositoryInterface $repository,
        private readonly UuidGenerator $generator,
        private readonly ?LoggerInterface $logger = null
    ) {}

    public function execute(): ExchangeRate
    {
        $usd = CurrencyCode::usd();
        $ves = CurrencyCode::ves();

        $scrapeResult = $this->scraper->fetchUsdRate();

        if (! $scrapeResult['success'] || $scrapeResult['rate'] <= 0) {
            $fallback = $this->repository->findActive($usd, $ves);

            if ($fallback) {
                $this->logger?->warning('Fallo en sincronización con BCV. Se mantiene la última tasa activa en el sistema.', [
                    'error' => $scrapeResult['error_message'] ?? 'Error desconocido',
                    'active_rate' => $fallback->getRate()->value(),
                ]);

                return $fallback;
            }

            throw new Exception(
                'No se pudo obtener la tasa oficial del BCV y no existe una tasa activa registrada en el sistema: '.
                ($scrapeResult['error_message'] ?? 'Error desconocido')
            );
        }

        // 1. Desactivar tasas anteriores
        $this->repository->deactivateAll($usd, $ves);

        // 2. Crear nueva entidad
        $exchangeRate = ExchangeRate::create(
            $this->generator,
            $usd,
            $ves,
            RateAmount::make($scrapeResult['rate']),
            RateSource::bcv(),
            RateDate::make($scrapeResult['rate_date']),
            true,
            [
                'synced_at' => date('c'),
                'source_url' => 'https://www.bcv.org.ve/',
                'raw_snippet' => $scrapeResult['raw_html'],
            ]
        );

        // 3. Persistir
        $this->repository->save($exchangeRate);

        $this->logger?->info("Tasa BCV sincronizada exitosamente: {$scrapeResult['rate']} VES/USD con fecha valor {$scrapeResult['rate_date']}");

        return $exchangeRate;
    }
}
