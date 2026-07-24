<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Src\User\Domain\Shared\Security\UuidGenerator;
use Src\User\Infrastructure\Security\LaravelUuidGenerator;

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
