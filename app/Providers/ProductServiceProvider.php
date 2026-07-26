<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Src\Product\Application\Contracts\Repositories\ProductRepositoryInterface;
use Src\Product\Infrastructure\Eloquent\Repositories\ProductRepository;
use Src\Shared\Domain\Contracts\UuidGenerator;
use Src\Shared\Infrastructure\Security\LaravelUuidGenerator;

class ProductServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
        $this->app->bind(UuidGenerator::class, LaravelUuidGenerator::class);
        $this->app->bind(ProductRepositoryInterface::class, ProductRepository::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
