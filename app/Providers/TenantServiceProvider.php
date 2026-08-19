<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Src\Tenant\Application\Contracts\Repositories\TenantOwnerRepositoryInterface;
use Src\Tenant\Application\Contracts\Repositories\TenantRepositoryInterface;
use Src\Tenant\Application\Contracts\Repositories\TenantUserRepositoryInterface;
use Src\Tenant\Infrastructure\Eloquent\Repositories\TenantOwnerRepository;
use Src\Tenant\Infrastructure\Eloquent\Repositories\TenantRepository;
use Src\Tenant\Infrastructure\Eloquent\Repositories\TenantUserRepository;

class TenantServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->bind(TenantRepositoryInterface::class, TenantRepository::class);
        $this->app->bind(TenantOwnerRepositoryInterface::class, TenantOwnerRepository::class);
        $this->app->bind(TenantUserRepositoryInterface::class, TenantUserRepository::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
