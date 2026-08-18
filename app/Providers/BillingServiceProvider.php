<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Src\Billing\Application\Contracts\Repositories\BillingProfileRepositoryInterface;
use Src\Billing\Application\Contracts\Repositories\InvoiceRepositoryInterface;
use Src\Billing\Infrastructure\Eloquent\Repositories\EloquentBillingProfileRepository;
use Src\Billing\Infrastructure\Eloquent\Repositories\EloquentInvoiceRepository;

class BillingServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->bind(BillingProfileRepositoryInterface::class, EloquentBillingProfileRepository::class);
        $this->app->bind(InvoiceRepositoryInterface::class, EloquentInvoiceRepository::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void {}
}
