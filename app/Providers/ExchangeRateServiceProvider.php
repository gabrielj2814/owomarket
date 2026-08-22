<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Src\ExchangeRate\Application\UseCase\CreateManualExchangeRateUseCase;
use Src\ExchangeRate\Domain\Contracts\BcvScraperInterface;
use Src\ExchangeRate\Domain\Contracts\ExchangeRateRepositoryInterface;
use Src\ExchangeRate\Domain\Contracts\StaleRateAlerter;
use Src\ExchangeRate\Infrastructure\Eloquent\Repositories\EloquentExchangeRateRepository;
use Src\ExchangeRate\Infrastructure\Notifications\MailStaleRateAlerter;
use Src\ExchangeRate\Infrastructure\Scrapers\BcvWebScraper;
use Src\Shared\Domain\Contracts\UuidGenerator;

class ExchangeRateServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->bind(
            ExchangeRateRepositoryInterface::class,
            EloquentExchangeRateRepository::class
        );

        $this->app->bind(
            BcvScraperInterface::class,
            BcvWebScraper::class
        );

        // Hallazgo N20: el canal por el que se avisa de una tasa congelada. Cambiar el
        // correo por un webhook es cambiar esta linea; el caso de uso no se entera.
        $this->app->bind(
            StaleRateAlerter::class,
            MailStaleRateAlerter::class
        );

        // Hallazgo Auditoria #4: la zona que decide la fecha valor de una tasa manual.
        $this->app->bind(
            CreateManualExchangeRateUseCase::class,
            fn ($app) => new CreateManualExchangeRateUseCase(
                $app->make(ExchangeRateRepositoryInterface::class),
                $app->make(UuidGenerator::class),
                (string) config('app.business_timezone', 'America/Caracas')
            )
        );
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                \Src\ExchangeRate\Infrastructure\Console\Commands\SyncBcvExchangeRateCommand::class,
            ]);
        }
    }
}
