<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Src\Tenant\Application\Contracts\Repositories\TenantOwnerRepositoryInterface;
use Src\Tenant\Application\Contracts\Repositories\TenantRepositoryInterface;
use Src\Tenant\Domain\Shared\Security\PasswordHasher;
use Src\Tenant\Domain\Shared\Security\PasswordValidator;
use Src\Tenant\Infrastructure\Eloquent\Repositories\TenantOwnerRepository;
use Src\Tenant\Infrastructure\Eloquent\Repositories\TenantRepository;
use Src\Tenant\Infrastructure\Security\LaravelPasswordHasher;
use Src\Tenant\Infrastructure\Security\StrictPasswordValidator;

class TenantServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
        $this->app->bind(PasswordHasher::class, LaravelPasswordHasher::class);
        $this->app->bind(PasswordValidator::class, StrictPasswordValidator::class);
        $this->app->bind(TenantRepositoryInterface::class, TenantRepository::class);
        $this->app->bind(TenantOwnerRepositoryInterface::class, TenantOwnerRepository::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
