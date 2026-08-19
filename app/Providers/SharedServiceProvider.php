<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Src\Shared\Domain\Contracts\PasswordHasher;
use Src\Shared\Domain\Contracts\PasswordValidator;
use Src\Shared\Domain\Contracts\UuidGenerator;
use Src\Shared\Infrastructure\Security\LaravelPasswordHasher;
use Src\Shared\Infrastructure\Security\LaravelUuidGenerator;
use Src\Shared\Infrastructure\Security\StrictPasswordValidator;

/**
 * Centraliza los bindings de infraestructura compartida (Shared Kernel)
 * usados por múltiples módulos. Antes de este provider, estos 3 bindings
 * estaban duplicados textualmente en AdminServiceProvider, AppServiceProvider,
 * AuthServiceProvider, TenantServiceProvider y UserServiceProvider.
 */
class SharedServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(UuidGenerator::class, LaravelUuidGenerator::class);
        $this->app->bind(PasswordHasher::class, LaravelPasswordHasher::class);
        $this->app->bind(PasswordValidator::class, StrictPasswordValidator::class);
    }

    public function boot(): void
    {
        //
    }
}
