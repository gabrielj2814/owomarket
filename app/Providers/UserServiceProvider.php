<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Src\Shared\Domain\Contracts\UuidGenerator;
use Src\Shared\Infrastructure\Security\LaravelUuidGenerator;

class UserServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->bind(UuidGenerator::class, LaravelUuidGenerator::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
