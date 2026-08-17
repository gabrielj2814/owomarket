<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Src\Shipping\Application\Contracts\ShippingRepositoryInterface;
use Src\Shipping\Infrastructure\Eloquent\Repositories\ShippingRepository;

final class ShippingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            ShippingRepositoryInterface::class,
            ShippingRepository::class
        );
    }

    public function boot(): void
    {
    }
}
