<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Src\ExchangeRate\Domain\Contracts\BcvScraperInterface;
use Src\ExchangeRate\Domain\Contracts\ExchangeRateRepositoryInterface;
use Src\ExchangeRate\Domain\Contracts\StaleRateAlerter;
use Src\ExchangeRate\Infrastructure\Eloquent\Repositories\EloquentExchangeRateRepository;
use Src\ExchangeRate\Infrastructure\Notifications\MailStaleRateAlerter;
use Src\ExchangeRate\Infrastructure\Scrapers\BcvWebScraper;

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
