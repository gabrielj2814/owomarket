<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Src\Billing\Application\Contracts\Repositories\BillingProfileRepositoryInterface;
use Src\Billing\Application\Contracts\Repositories\InvoiceRepositoryInterface;
use Src\Billing\Application\Contracts\Services\InvoiceMailerInterface;
use Src\Billing\Application\Contracts\Services\InvoicePdfGeneratorInterface;
use Src\Billing\Infrastructure\Eloquent\Repositories\EloquentBillingProfileRepository;
use Src\Billing\Infrastructure\Eloquent\Repositories\EloquentInvoiceRepository;
use Src\Billing\Infrastructure\Services\DomPdfInvoiceGeneratorService;
use Src\Billing\Infrastructure\Services\LaravelInvoiceMailerService;

class BillingServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->bind(BillingProfileRepositoryInterface::class, EloquentBillingProfileRepository::class);
        $this->app->bind(InvoiceRepositoryInterface::class, EloquentInvoiceRepository::class);
        $this->app->bind(InvoicePdfGeneratorInterface::class, DomPdfInvoiceGeneratorService::class);
        $this->app->bind(InvoiceMailerInterface::class, LaravelInvoiceMailerService::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void {}
}
