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
        if ($this->app->runningInConsole()) {
            // Pendiente P1 / hallazgo N22: la Fase 2.1 veto `RootUserSeeder` fuera de
            // desarrollo, asi que una instalacion nueva se quedaba sin forma de crear el
            // primer superadministrador. Este comando es ese camino.
            $this->commands([
                \Src\Admin\Infrastructure\Console\Commands\CreateSuperAdminCommand::class,
            ]);
        }
    }
}
