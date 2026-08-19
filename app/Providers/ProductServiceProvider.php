<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Src\Product\Application\Contracts\ProductMediaStorageInterface;
use Src\Product\Application\Contracts\ProductRepositoryInterface;
use Src\Product\Infrastructure\Eloquent\Repositories\ProductRepository;
use Src\Product\Infrastructure\Services\LaravelProductMediaStorageService;

class ProductServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->bind(ProductRepositoryInterface::class, ProductRepository::class);
        $this->app->bind(ProductMediaStorageInterface::class, LaravelProductMediaStorageService::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void {}
}
