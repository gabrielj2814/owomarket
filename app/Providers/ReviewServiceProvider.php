<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Src\Review\Application\Repositories\ReviewRepositoryInterface;
use Src\Review\Infrastructure\Eloquent\Repositories\EloquentReviewRepository;

class ReviewServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->bind(
            ReviewRepositoryInterface::class,
            EloquentReviewRepository::class
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
