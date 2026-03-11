<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Src\Product\Application\Contracts\Repositories\ProductRepositoryInterface;
use Src\Product\Infrastructure\Eloquent\Repositories\ProductRepository;

class ProductServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //

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
