<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Src\Shared\Domain\Contracts\PasswordHasher;
use Src\Shared\Domain\Contracts\PasswordValidator;
use Src\Shared\Domain\Contracts\UuidGenerator;
use Src\Shared\Infrastructure\Security\LaravelPasswordHasher;
use Src\Shared\Infrastructure\Security\LaravelUuidGenerator;
use Src\Shared\Infrastructure\Security\StrictPasswordValidator;
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
        //
        $this->app->bind(UuidGenerator::class, LaravelUuidGenerator::class);
        $this->app->bind(PasswordHasher::class, LaravelPasswordHasher::class);
        $this->app->bind(PasswordValidator::class, StrictPasswordValidator::class);
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
