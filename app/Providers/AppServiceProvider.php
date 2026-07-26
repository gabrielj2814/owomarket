<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Laravel\Sanctum\Sanctum;
use Src\Admin\Infrastructure\Services\AuthApiClient;
use Src\Authentication\Application\Contracts\AuthServices;
use Src\Authentication\Application\Contracts\UserServices;
use Src\Authentication\Infrastructure\Eloquent\Models\PersonalAccessToken;
use Src\Authentication\Infrastructure\Services\UserApiClient;
use Src\Shared\Domain\Contracts\PasswordHasher;
use Src\Shared\Domain\Contracts\PasswordValidator;
use Src\Shared\Domain\Contracts\UuidGenerator;
use Src\Shared\Infrastructure\Security\LaravelPasswordHasher;
use Src\Shared\Infrastructure\Security\LaravelUuidGenerator;
use Src\Shared\Infrastructure\Security\StrictPasswordValidator;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
        $this->app->bind(UuidGenerator::class, LaravelUuidGenerator::class);
        $this->app->bind(PasswordHasher::class, LaravelPasswordHasher::class);
        $this->app->bind(PasswordValidator::class, StrictPasswordValidator::class);

        $this->app->bind(UserServices::class,UserApiClient::class);
        $this->app->bind(AuthServices::class,AuthApiClient::class);

    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
        Sanctum::usePersonalAccessTokenModel(PersonalAccessToken::class);

        if (tenancy()->initialized) {
            $currentDomain = tenancy()->tenant->domains->first()->domain;
            \URL::forceRootUrl('http://' . $currentDomain);

            // Para Vite/Webpack
            config(['app.url' => 'http://' . $currentDomain]);
        }
    }
}
