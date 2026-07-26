<?php

namespace App\Providers;

use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Support\ServiceProvider;
use Src\Admin\Application\Contracts\Repositories\AdminRepositoryInterface;
use Src\Admin\Infrastructure\Eloquent\Repositories\AdminRepository;
use Src\Shared\Domain\Contracts\PasswordHasher;
use Src\Shared\Domain\Contracts\PasswordValidator;
use Src\Shared\Domain\Contracts\UuidGenerator;
use Src\Shared\Infrastructure\Security\LaravelPasswordHasher;
use Src\Shared\Infrastructure\Security\LaravelUuidGenerator;
use Src\Shared\Infrastructure\Security\StrictPasswordValidator;

class AdminServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
        //  // Configurar el PasswordHasher con el driver de Laravel
        // $this->app->bind(PasswordHasher::class, function ($app) {
        //     // $app->make(Hasher::class) devuelve el Hasher configurado en Laravel
        //     return new LaravelPasswordHasher(
        //         $app->make(Hasher::class)
        //     );
        // });

        $this->app->bind(UuidGenerator::class, LaravelUuidGenerator::class);
        $this->app->bind(PasswordHasher::class, LaravelPasswordHasher::class);
        $this->app->bind(PasswordValidator::class, StrictPasswordValidator::class);
        $this->app->bind(AdminRepositoryInterface::class, AdminRepository::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
