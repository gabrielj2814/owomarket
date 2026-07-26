<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Src\Authentication\Application\Contracts\Repositories\AuthUserRepositoryInterface;
use Src\Authentication\Application\Contracts\Repositories\LoginWebRepositoryInterface;
use Src\Authentication\Application\Contracts\Repositories\PersonalAccessTokenRepositoryInterface;
use Src\Authentication\Application\Contracts\Repositories\UserRepositoryInterface;
use Src\Authentication\Infrastructure\Eloquent\Repositories\AuthUserRepository;
use Src\Authentication\Infrastructure\Eloquent\Repositories\LoginWebRepository;
use Src\Authentication\Infrastructure\Eloquent\Repositories\PersonalAccessTokenRepository;
use Src\Authentication\Infrastructure\Eloquent\Repositories\UserRepository;
use Src\Shared\Domain\Contracts\PasswordHasher;
use Src\Shared\Domain\Contracts\PasswordValidator;
use Src\Shared\Domain\Contracts\UuidGenerator;
use Src\Shared\Infrastructure\Security\LaravelPasswordHasher;
use Src\Shared\Infrastructure\Security\LaravelUuidGenerator;
use Src\Shared\Infrastructure\Security\StrictPasswordValidator;

class AuthServiceProvider extends ServiceProvider
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
        $this->app->bind(LoginWebRepositoryInterface::class, LoginWebRepository::class);
        $this->app->bind(UserRepositoryInterface::class, UserRepository::class);
        $this->app->bind(AuthUserRepositoryInterface::class, AuthUserRepository::class);
        $this->app->bind(PersonalAccessTokenRepositoryInterface::class, PersonalAccessTokenRepository::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
