<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Src\Brand\Application\Contracts\BrandRepositoryInterface;
use Src\Brand\Infrastructure\Eloquent\Repositories\BrandRepository;

class BrandServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->bind(
            BrandRepositoryInterface::class,
            BrandRepository::class
        );
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
