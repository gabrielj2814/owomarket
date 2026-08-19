<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Src\Order\Application\Contracts\Repositories\OrderRepositoryInterface;
use Src\Order\Infrastructure\Eloquent\Repositories\EloquentOrderRepository;

final class OrderServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            OrderRepositoryInterface::class,
            EloquentOrderRepository::class
        );
    }

    public function boot(): void {}
}
