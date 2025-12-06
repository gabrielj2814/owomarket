<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Src\Authentication\Application\Contracts\Repositories\AuthUserRepositoryInterface;
use Src\Authentication\Application\Contracts\Repositories\LoginWebRepositoryInterface;
use Src\Authentication\Application\Contracts\Repositories\PersonalAccessTokenRepositoryInterface;
use Src\Authentication\Application\Contracts\Repositories\UserRepositoryInterface;
use Src\Authentication\Domain\Shared\Security\PasswordHasher;
use Src\Authentication\Domain\Shared\Security\PasswordValidator;
use Src\Authentication\Infrastructure\Eloquent\Repositories\AuthUserRepository;
use Src\Authentication\Infrastructure\Eloquent\Repositories\LoginWebRepository;
use Src\Authentication\Infrastructure\Eloquent\Repositories\PersonalAccessTokenRepository;
use Src\Authentication\Infrastructure\Eloquent\Repositories\UserRepository;
use Src\Authentication\Infrastructure\Security\LaravelPasswordHasher;
use Src\Authentication\Infrastructure\Security\StrictPasswordValidator;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //

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
