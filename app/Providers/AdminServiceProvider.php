<?php

namespace App\Providers;

use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Support\ServiceProvider;
use Src\Admin\Application\Contracts\Repositories\AdminRepositoryInterface;
use Src\Admin\Domain\Shared\Security\PasswordHasher;
use Src\Admin\Domain\Shared\Security\PasswordValidator;
use Src\Admin\Infrastructure\Eloquent\Repositories\AdminRepository;
use Src\Admin\Infrastructure\Security\LaravelPasswordHasher;
use Src\Admin\Infrastructure\Security\StrictPasswordValidator;

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
