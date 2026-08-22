<?php

declare(strict_types=1);

namespace Src\ExchangeRate\Application\UseCase;

use DateTimeImmutable;
use Exception;
use Illuminate\Support\Facades\DB;
use Psr\Log\LoggerInterface;
use Src\ExchangeRate\Domain\Contracts\BcvScraperInterface;
use Src\ExchangeRate\Domain\Contracts\ExchangeRateRepositoryInterface;
use Src\ExchangeRate\Domain\Contracts\StaleRateAlerter;
use Src\ExchangeRate\Domain\Entities\ExchangeRate;
use Src\ExchangeRate\Domain\ValueObjects\CurrencyCode;
use Src\ExchangeRate\Domain\ValueObjects\RateAmount;
use Src\ExchangeRate\Domain\ValueObjects\RateDate;
use Src\ExchangeRate\Domain\ValueObjects\RateSource;
use Src\Shared\Domain\Contracts\UuidGenerator;

final class SyncBcvExchangeRateUseCase
{
    /**
     * Días de antigüedad de la tasa activa a partir de los cuales el fallback deja de
     * ser un incidente puntual y pasa a registrarse como error (hallazgo D4).
     */
    private const STALE_RATE_ALERT_DAYS = 3;

    public function __construct(
        private readonly BcvScraperInterface $scraper,
        private readonly ExchangeRateRepositoryInterface $repository,
        private readonly UuidGenerator $generator,
        private readonly ?LoggerInterface $logger = null,
        private readonly ?StaleRateAlerter $alerter = null
    ) {}

    public function execute(): ExchangeRate
    {
        $usd = CurrencyCode::usd();
        $ves = CurrencyCode::ves();

        $scrapeResult = $this->scraper->fetchUsdRate();

        if (! $scrapeResult['success'] || $scrapeResult['rate'] <= 0) {
            $fallback = $this->repository->findActive($usd, $ves);

            if ($fallback) {
                $this->reportFallback($fallback, $scrapeResult['error_message'] ?? 'Error desconocido');

                return $fallback;
            }

            throw new Exception(
                'No se pudo obtener la tasa oficial del BCV y no existe una tasa activa registrada en el sistema: '.
                ($scrapeResult['error_message'] ?? 'Error desconocido')
            );
        }

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

        // Hallazgo D3: desactivar y guardar eran dos escrituras sueltas. Si el `save()`
        // fallaba o el proceso moría entre ambas, el sistema quedaba SIN NINGUNA tasa
        // activa, y a partir de ahí todo se convertía con la tasa 1.0 del fallback.
        // Ahora, o quedan las dos, o no queda ninguna: nunca el hueco intermedio.
        DB::transaction(function () use ($usd, $ves, $exchangeRate): void {
            $this->repository->deactivateAll($usd, $ves);
            $this->repository->save($exchangeRate);
        });

        $this->logger?->info("Tasa BCV sincronizada exitosamente: {$scrapeResult['rate']} VES/USD con fecha valor {$scrapeResult['rate_date']}");

        return $exchangeRate;
    }

    /**
     * Hallazgo D4: el fallback sólo dejaba un `warning` en el log, así que el sitio podía
     * pasar semanas facturando con una tasa congelada sin que nadie se enterara. Se
     * escala a `error` en cuanto la tasa activa acumula varios días de antigüedad.
     */
    private function reportFallback(ExchangeRate $fallback, string $errorMessage): void
    {
        $rateDate = $fallback->getRateDate()->toDateTime();
        $daysStale = (int) $rateDate->diff(new DateTimeImmutable('today'))->days;

        $context = [
            'error' => $errorMessage,
            'active_rate' => $fallback->getRate()->value(),
            'rate_date' => $fallback->getRateDate()->value(),
            'days_stale' => $daysStale,
        ];

        if ($daysStale >= self::STALE_RATE_ALERT_DAYS) {
            $this->logger?->error(
                "Fallo en sincronización con BCV: la tasa activa lleva {$daysStale} días sin actualizarse. ".
                'Todo el sitio está facturando con una tasa desactualizada.',
                $context
            );

            // Hallazgo N20: el `error` del log no despierta a nadie. El aviso sale ahora
            // por un canal que alguien lee. El alerter es opcional a propósito, para que
            // el caso de uso siga instanciable sin contenedor (así lo hacen los tests).
            $this->alerter?->alertStaleRate($fallback, $daysStale, $errorMessage);

            return;
        }

        $this->logger?->warning('Fallo en sincronización con BCV. Se mantiene la última tasa activa en el sistema.', $context);
    }
}
