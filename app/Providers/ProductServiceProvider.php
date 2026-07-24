<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Src\Product\Application\Contracts\Repositories\ProductRepositoryInterface;
use Src\Product\Domain\Shared\Security\UuidGenerator;
use Src\Product\Infrastructure\Eloquent\Repositories\ProductRepository;
use Src\Product\Infrastructure\Security\LaravelUuidGenerator;

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
