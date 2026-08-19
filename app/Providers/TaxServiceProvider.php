<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Src\Tax\Application\Contracts\TaxRateRepositoryInterface;
use Src\Tax\Infrastructure\Eloquent\Repositories\TaxRateRepository;

final class TaxServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            TaxRateRepositoryInterface::class,
            TaxRateRepository::class
        );
    }

    public function boot(): void {}
}
