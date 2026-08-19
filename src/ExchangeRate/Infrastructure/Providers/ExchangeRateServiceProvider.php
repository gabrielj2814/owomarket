<?php

declare(strict_types=1);

namespace Src\ExchangeRate\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Src\ExchangeRate\Domain\Contracts\ExchangeRateRepositoryInterface;
use Src\ExchangeRate\Infrastructure\Eloquent\Repositories\EloquentExchangeRateRepository;

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
