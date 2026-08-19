<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Src\Admin\Application\Contracts\Repositories\AdminRepositoryInterface;
use Src\Admin\Infrastructure\Eloquent\Repositories\AdminRepository;

class AdminServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->bind(AdminRepositoryInterface::class, AdminRepository::class);
        $this->app->bind(
            \Src\Admin\Application\Contracts\Services\AvatarStorageInterface::class,
            \Src\Admin\Infrastructure\Services\LaravelAvatarStorageService::class
        );
        $this->app->bind(
            \Src\Admin\Application\Contracts\Services\SecurityPinMailerInterface::class,
            \Src\Admin\Infrastructure\Services\LaravelSecurityPinMailerService::class
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
